<?php

namespace App\Jobs;

use App\Enums\AiProcessingStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\JournalEntryStatus;
use App\Models\AiProcessingLog;
use App\Models\ChartOfAccount;
use App\Models\Document;
use App\Models\DocumentExtraction;
use App\Models\DocumentLineItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public Document $document,
        public ?string  $localTempPath = null,
    ) {}

    public function handle(): void
    {
        $this->document->update(['status' => DocumentStatus::AiProcessing]);

        $startedAt = now();

        try {
            // ── Load file ──────────────────────────────────────────────────────
            if ($this->localTempPath && Storage::disk('local')->exists($this->localTempPath)) {
                $fileContent = Storage::disk('local')->get($this->localTempPath);
            } else {
                $fileContent = Storage::disk('google')->get($this->document->storage_path);
            }

            if (empty($fileContent)) {
                throw new \RuntimeException("Cannot read file: {$this->document->storage_path}");
            }

            $base64 = base64_encode($fileContent);

            // ── Chart of accounts ──────────────────────────────────────────────
            $accounts     = ChartOfAccount::where('allows_posting', true)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['code', 'name']);

            $accountsList = $accounts->map(fn ($a) => "{$a->code} — {$a->name}")->join("\n");

            $contentBlock = $this->buildContentBlock($base64, $this->document->mime_type);

            // ── Branch by document type ────────────────────────────────────────
            if ($this->document->type === DocumentType::BankStatement) {
                $this->processBankStatement($contentBlock, $accounts, $accountsList, $startedAt);
            } else {
                $this->processInvoice($contentBlock, $accounts, $accountsList, $startedAt);
            }

        } catch (\Throwable $e) {
            Log::error('ProcessDocumentJob failed', [
                'document_id' => $this->document->id,
                'error'       => $e->getMessage(),
            ]);

            $this->document->update(['status' => DocumentStatus::Rejected]);

            AiProcessingLog::create([
                'document_id'   => $this->document->id,
                'model'         => config('services.anthropic.model'),
                'input_tokens'  => 0,
                'output_tokens' => 0,
                'cost_usd'      => 0,
                'duration_ms'   => (int) $startedAt->diffInMilliseconds(now()),
                'status'        => AiProcessingStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            $this->cleanupLocalTemp();
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  BANK STATEMENT PROCESSING
    // ═══════════════════════════════════════════════════════════════════════════

    private function processBankStatement(array $contentBlock, $accounts, string $accountsList, $startedAt): void
    {
        $openBalances = $this->openBalancesByPartner($this->document->company_id);

        $response = $this->callClaude($contentBlock, $this->buildBankStatementPrompt($accountsList, $openBalances));

        $durationMs = (int) $startedAt->diffInMilliseconds(now());

        if ($response->failed()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        $rawText    = $response->json('content.0.text', '');
        $data       = $this->parseJson($rawText);
        $statements = $data['statements'] ?? [];

        if (empty($statements)) {
            throw new \RuntimeException('Claude returned no bank statements.');
        }

        $inputTokens  = $response->json('usage.input_tokens', 0);
        $outputTokens = $response->json('usage.output_tokens', 0);

        $this->logAi($this->document->id, $durationMs, $inputTokens, $outputTokens);

        if (count($statements) === 1) {
            // Single statement — store directly on the uploaded document
            $this->storeSingleBankStatement($this->document, $statements[0], $accounts, $openBalances);
            $this->document->update([
                'status'          => DocumentStatus::AiProcessed,
                'ai_raw_response' => $data,
                'ai_confidence'   => $data['confidence'] ?? null,
                'ai_processed_at' => now(),
            ]);
        } else {
            // Multiple statements — create one child Document per statement
            foreach ($statements as $stmt) {
                $label = $stmt['statement_number'] ?? (array_search($stmt, $statements) + 1);
                $child = Document::create([
                    'company_id'         => $this->document->company_id,
                    'parent_document_id' => $this->document->id,
                    'uploaded_by'        => $this->document->uploaded_by,
                    'type'               => DocumentType::BankStatement,
                    'status'             => DocumentStatus::AiProcessed,
                    'intake_channel'     => $this->document->intake_channel,
                    'original_filename'  => "Извод {$label} — {$this->document->original_filename}",
                    'storage_path'       => $this->document->storage_path,
                    'drive_file_id'      => $this->document->drive_file_id,
                    'mime_type'          => $this->document->mime_type,
                    'file_size'          => $this->document->file_size,
                    'ai_confidence'      => $data['confidence'] ?? null,
                    'ai_processed_at'    => now(),
                ]);
                $this->storeSingleBankStatement($child, $stmt, $accounts, $openBalances);
            }

            // Mark original as split (cannot be booked directly)
            $this->document->update([
                'status'          => DocumentStatus::Split,
                'ai_raw_response' => $data,
                'ai_processed_at' => now(),
                'notes'           => 'PDF-от содржеше ' . count($statements) . ' изводи — поделен во посебни документи.',
            ]);
        }

        $this->cleanupLocalTemp();
    }

    private function storeSingleBankStatement(Document $doc, array $stmt, $accounts, array $openBalances = []): void
    {
        DocumentExtraction::create([
            'document_id'     => $doc->id,
            'bank_name'       => $stmt['bank_name'] ?? null,
            'account_number'  => $stmt['account_number'] ?? null,
            'statement_number'=> (string) ($stmt['statement_number'] ?? ''),
            'document_date'   => $stmt['statement_date'] ?? null,
            'currency'        => $stmt['currency'] ?? 'MKD',
            'opening_balance' => $stmt['opening_balance'] ?? null,
            'closing_balance' => $stmt['closing_balance'] ?? null,
            'total_debit'     => $stmt['total_debit'] ?? 0,
            'total_credit'    => $stmt['total_credit'] ?? 0,
            'subtotal'        => ($stmt['total_debit'] ?? 0) + ($stmt['total_credit'] ?? 0),
            'total_amount'    => $stmt['closing_balance'] ?? 0,
        ]);

        foreach ($stmt['transactions'] ?? [] as $i => $tx) {
            $acctCode = isset($tx['suggested_account_code'])
                ? $accounts->firstWhere('code', $tx['suggested_account_code'])?->code
                : null;

            // Match Claude's suggested partner name back to one of the candidates
            // we fed it (never a free DB-wide search) so the suggestion is always
            // an actual open receivable/payable — Тамара confirms/changes it.
            $matchedName    = $tx['matched_kontragent_name'] ?? null;
            $matchedBalance = $matchedName
                ? collect($openBalances)->first(fn ($b) => mb_strtolower($b['name']) === mb_strtolower($matchedName))
                : null;

            DocumentLineItem::create([
                'document_id'                 => $doc->id,
                'sort_order'                  => $i,
                'description'                 => $tx['description'] ?? '',
                'reference'                   => $tx['reference'] ?? null,
                'transaction_date'            => $tx['date'] ?? null,
                'debit'                       => $tx['debit'] ?? 0,
                'credit'                      => $tx['credit'] ?? 0,
                'total_amount'                => max((float)($tx['debit'] ?? 0), (float)($tx['credit'] ?? 0)),
                'suggested_account_code'      => $acctCode,
                'ai_confidence'               => $tx['ai_confidence'] ?? null,
                'suggested_kontragent_id'     => $matchedBalance['kontragent_id'] ?? null,
                'suggested_closing_reference' => $matchedBalance ? ($tx['matched_reference'] ?? null) : null,
            ]);
        }
    }

    /**
     * Open (non-zero) receivable/payable balances per partner, for accounts
     * 120 (Побарувања) and 220 (Обврски). Fed to Claude as match candidates
     * when reconciling a bank statement — keeps AI suggestions constrained
     * to real, currently-open balances instead of a free-form name guess.
     */
    private function openBalancesByPartner(int $companyId): array
    {
        return DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('kontragenti as k', 'k.id', '=', 'jel.kontragent_id')
            ->where('je.company_id', $companyId)
            ->where('je.status', JournalEntryStatus::Posted->value)
            ->whereIn('jel.account_code', ['120', '220'])
            ->selectRaw('k.id, k.name, k.edb, jel.account_code, SUM(jel.debit) as debit, SUM(jel.credit) as credit')
            ->groupBy('k.id', 'k.name', 'k.edb', 'jel.account_code')
            ->havingRaw('ABS(SUM(jel.debit) - SUM(jel.credit)) > 0.01')
            ->get()
            ->map(fn ($r) => [
                'kontragent_id' => $r->id,
                'name'          => $r->name,
                'edb'           => $r->edb,
                'account_code'  => $r->account_code,
                'balance'       => round((float) $r->debit - (float) $r->credit, 2),
            ])
            ->all();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  INVOICE PROCESSING  (unchanged logic, extracted to method)
    // ═══════════════════════════════════════════════════════════════════════════

    private function processInvoice(array $contentBlock, $accounts, string $accountsList, $startedAt): void
    {
        $response = $this->callClaude($contentBlock, $this->buildInvoicePrompt($accountsList));

        $durationMs = (int) $startedAt->diffInMilliseconds(now());

        if ($response->failed()) {
            throw new \RuntimeException('Claude API error: ' . $response->body());
        }

        $rawText = $response->json('content.0.text', '');
        $data    = $this->parseJson($rawText);

        DocumentExtraction::create([
            'document_id'       => $this->document->id,
            'vendor_name'       => $data['vendor_name'] ?? null,
            'vendor_tax_id'     => $data['vendor_tax_id'] ?? null,
            'vendor_vat_number' => $data['vendor_vat_number'] ?? null,
            'customer_name'     => $data['customer_name'] ?? null,
            'customer_tax_id'   => $data['customer_tax_id'] ?? null,
            'document_number'   => $data['document_number'] ?? null,
            'document_date'     => $data['document_date'] ?? null,
            'due_date'          => $data['due_date'] ?? null,
            'currency'          => $data['currency'] ?? 'MKD',
            'subtotal'          => $data['subtotal'] ?? 0,
            'vat_amount'        => $data['vat_amount'] ?? 0,
            'total_amount'      => $data['total_amount'] ?? 0,
        ]);

        foreach ($data['line_items'] ?? [] as $i => $item) {
            $acctCode = isset($item['suggested_account_code'])
                ? $accounts->firstWhere('code', $item['suggested_account_code'])?->code
                : null;

            DocumentLineItem::create([
                'document_id'            => $this->document->id,
                'sort_order'             => $i,
                'description'            => $item['description'] ?? '',
                'quantity'               => $item['quantity'] ?? null,
                'unit'                   => $item['unit'] ?? null,
                'unit_price'             => $item['unit_price'] ?? null,
                'vat_rate'               => $item['vat_rate'] ?? 0,
                'vat_amount'             => $item['vat_amount'] ?? 0,
                'total_amount'           => $item['total_amount'] ?? 0,
                'suggested_account_code' => $acctCode,
                'ai_confidence'          => $item['ai_confidence'] ?? null,
            ]);
        }

        $inputTokens  = $response->json('usage.input_tokens', 0);
        $outputTokens = $response->json('usage.output_tokens', 0);

        $this->logAi($this->document->id, $durationMs, $inputTokens, $outputTokens);

        $this->document->update([
            'status'          => DocumentStatus::AiProcessed,
            'ai_raw_response' => $data,
            'ai_confidence'   => $data['confidence'] ?? null,
            'ai_processed_at' => now(),
        ]);

        $this->cleanupLocalTemp();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  PROMPTS
    // ═══════════════════════════════════════════════════════════════════════════

    private function buildBankStatementPrompt(string $accountsList, array $openBalances = []): string
    {
        $openBalancesList = empty($openBalances)
            ? '(нема отворени салда во системот моментално)'
            : collect($openBalances)
                ->map(fn ($b) => "{$b['name']} (ЕДБ {$b['edb']}) — конто {$b['account_code']} — отворено {$b['balance']}")
                ->join("\n");

        return <<<PROMPT
Ова е банкарски извод (или повеќе изводи) на македонска компанија. Анализирај ги и врати САМО валиден JSON без никаков дополнителен текст.

Македонски сметковен план (за предлагање контрапартиски сметки):
{$accountsList}

Отворени побарувања/обврски по фирма (за поврзување на уплата/исплата со конкретна фирма — користи ГИ ОВИЕ имиња точно, не измислувај нови):
{$openBalancesList}

ВО PDF-ОТ МОЖЕ ДА ИМА ПОВЕЌЕ ИЗВОДИ. Екстрактирај ГИ СИТЕ и врати ги во полето "statements" (низа).

Врати го ТОЧНО овој JSON формат:

{
  "confidence": 0.95,
  "statements": [
    {
      "bank_name": "Стопанска Банка АД Скопје",
      "account_number": "200000123456789",
      "statement_number": "29",
      "statement_date": "YYYY-MM-DD",
      "currency": "MKD",
      "opening_balance": 150000.00,
      "closing_balance": 165000.00,
      "total_debit": 25000.00,
      "total_credit": 40000.00,
      "transactions": [
        {
          "date": "YYYY-MM-DD",
          "description": "опис на трансакцијата",
          "reference": "референца или налог број",
          "debit": 0.00,
          "credit": 25000.00,
          "suggested_account_code": "120",
          "matched_kontragent_name": "точно име од листата со отворени салда, или null",
          "matched_reference": "нпр. 'ф-ра: 29/23' или друга референца што укажува која фактура се затвора, или null",
          "ai_confidence": 0.85
        }
      ]
    }
  ]
}

ПРАВИЛА:
1. Секој извод (statement) во PDF-от → посебен елемент во "statements" низата
2. За секоја трансакција:
   - Ако парите ВЛЕГУВААТ на банкарска сметка (уплата, прием) → credit > 0, debit = 0
   - Ако парите ИЗЛЕГУВААТ од банкарска сметка (исплата, плаќање) → debit > 0, credit = 0
3. total_debit = збир на сите debit трансакции во изводот
4. total_credit = збир на сите credit трансакции во изводот
5. suggested_account_code: предложи КОНТРАПАРТИСКА сметка (НЕ 100 — тоа е сметката на банката)
   - За уплати од купувачи: 120 (Побарувања од купувачи во земјата)
   - За исплати кон добавувачи: 220 (Обврски спрема добавувачи во земјата)
   - За плати: 240 (Обврски за плата и надоместоци на плата)
   - За даноци и придонеси: 230 (ДДВ) или 236 (придонеси)
   - За трошоци: 449 (Останати трошоци на работењето)
   - За останато: избери најблиска сметка од листата подолу
6. matched_kontragent_name: ако износот и описот на трансакцијата одговараат на некоја фирма од листата со отворени салда погоре, врати го НЕЈЗИНОТО ТОЧНО ИМЕ (копирај го точно, не менувај го). Ако нема добро совпаѓање, врати null.
7. matched_reference: краток текст што укажува која конкретна фактура/документ се затвора (пр. број на фактура споменат во описот), или null ако не може да се одреди
8. Датумите МОРА да бидат YYYY-MM-DD
9. Броевите МОРА да бидат децимали (не стрингови)
10. Врати САМО JSON, без markdown, без објаснувања
PROMPT;
    }

    private function buildInvoicePrompt(string $accountsList): string
    {
        return <<<PROMPT
Ова е сметководствен документ на македонска компанија. Анализирај го и врати САМО валиден JSON без никаков дополнителен текст.

Македонски сметковен план (за предлагање сметки):
{$accountsList}

Извади ги следните податоци и врати ги во овој точен JSON формат:

{
  "confidence": 0.95,
  "vendor_name": "Име на добавувачот",
  "vendor_tax_id": "ЕМБС или даночен број на добавувач",
  "vendor_vat_number": "ДДВ број на добавувач",
  "customer_name": "Име на купувачот",
  "customer_tax_id": "ЕМБС или даночен број на купувач",
  "document_number": "број на документот",
  "document_date": "YYYY-MM-DD",
  "due_date": "YYYY-MM-DD или null",
  "currency": "MKD",
  "subtotal": 1000.00,
  "vat_amount": 180.00,
  "total_amount": 1180.00,
  "line_items": [
    {
      "description": "опис на ставката",
      "quantity": 1.0,
      "unit": "ком",
      "unit_price": 1000.00,
      "vat_rate": 18.0,
      "vat_amount": 180.00,
      "total_amount": 1180.00,
      "suggested_account_code": "401",
      "ai_confidence": 0.90
    }
  ]
}

Правила:
- confidence е твојата општа доверба во точноста (0.0-1.0)
- За suggested_account_code — избери само кодови кои постојат во сметковниот план погоре
- Ако некое поле не можеш да го прочиташ, стави null
- Датумите мора да бидат во формат YYYY-MM-DD
- Броевите мора да бидат децимали, не стрингови
- Врати САМО JSON, без markdown, без објаснувања
PROMPT;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    private function callClaude(array $contentBlock, string $prompt): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => config('services.anthropic.version'),
            'content-type'      => 'application/json',
        ])->timeout(90)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 8192,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    $contentBlock,
                    ['type' => 'text', 'text' => $prompt],
                ],
            ]],
        ]);
    }

    private function buildContentBlock(string $base64, string $mimeType): array
    {
        if ($mimeType === 'application/pdf') {
            return [
                'type'   => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64],
            ];
        }

        return [
            'type'   => 'image',
            'source' => ['type' => 'base64', 'media_type' => $mimeType, 'data' => $base64],
        ];
    }

    private function logAi(int $documentId, int $durationMs, int $inputTokens, int $outputTokens): void
    {
        AiProcessingLog::create([
            'document_id'   => $documentId,
            'model'         => config('services.anthropic.model'),
            'input_tokens'  => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd'      => ($inputTokens * 0.000015) + ($outputTokens * 0.000075),
            'duration_ms'   => $durationMs,
            'status'        => AiProcessingStatus::Success,
        ]);
    }

    private function parseJson(string $text): array
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
        }

        $data = json_decode(trim($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Claude returned invalid JSON: ' . json_last_error_msg());
        }

        return $data;
    }

    private function cleanupLocalTemp(): void
    {
        if ($this->localTempPath) {
            Storage::disk('local')->delete($this->localTempPath);
        }
    }
}