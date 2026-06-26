<?php

namespace App\Jobs;

use App\Enums\AiProcessingStatus;
use App\Enums\DocumentStatus;
use App\Models\AiProcessingLog;
use App\Models\ChartOfAccount;
use App\Models\DocumentExtraction;
use App\Models\DocumentLineItem;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public Document $document) {}

    public function handle(): void
    {
        $this->document->update(['status' => DocumentStatus::AiProcessing]);

        $startedAt = now();

        try {
            $fileContent = Storage::disk('google')->get($this->document->storage_path);
            $base64      = base64_encode($fileContent);

            $accounts     = ChartOfAccount::where('allows_posting', true)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['code', 'name']);

            $accountsList = $accounts->map(fn($a) => "{$a->code} — {$a->name}")->join("\n");

            $contentBlock = $this->buildContentBlock($base64, $this->document->mime_type);

            $payload = [
                'model'      => config('services.anthropic.model'),
                'max_tokens' => 4096,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            $contentBlock,
                            ['type' => 'text', 'text' => $this->buildPrompt($accountsList)],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => config('services.anthropic.version'),
                'content-type'      => 'application/json',
            ])->timeout(90)->post('https://api.anthropic.com/v1/messages', $payload);

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
                $accountCode = isset($item['suggested_account_code'])
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
                    'suggested_account_code' => $accountCode,
                    'ai_confidence'          => $item['ai_confidence'] ?? null,
                ]);
            }

            $inputTokens  = $response->json('usage.input_tokens', 0);
            $outputTokens = $response->json('usage.output_tokens', 0);

            $this->document->update([
                'status'           => DocumentStatus::AiProcessed,
                'ai_raw_response'  => $data,
                'ai_confidence'    => $data['confidence'] ?? null,
                'ai_processed_at'  => now(),
            ]);

            AiProcessingLog::create([
                'document_id'   => $this->document->id,
                'model'         => config('services.anthropic.model'),
                'input_tokens'  => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_usd'      => ($inputTokens * 0.000015) + ($outputTokens * 0.000075),
                'duration_ms'   => $durationMs,
                'status'        => AiProcessingStatus::Success,
            ]);

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

            throw $e;
        }
    }

    private function buildContentBlock(string $base64, string $mimeType): array
    {
        if ($mimeType === 'application/pdf') {
            return [
                'type'   => 'document',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => 'application/pdf',
                    'data'       => $base64,
                ],
            ];
        }

        return [
            'type'   => 'image',
            'source' => [
                'type'       => 'base64',
                'media_type' => $mimeType,
                'data'       => $base64,
            ],
        ];
    }

    private function buildPrompt(string $accountsList): string
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
- Врати САМО JSON, без markdown, без objаснувања
PROMPT;
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
}
