<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\JournalEntryStatus;
use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\ChartOfAccount;
use App\Models\Document;
use App\Models\JournalEntry;
use App\Models\JournalGroup;
use App\Models\Kontragent;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Services\MonthlyJournalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{

    public function forBooking(Request $request): JsonResponse
    {
        $companyId = $this->currentCompanyId($request);
        $groupCode = (int) $request->group_code;
        $year      = (int) $request->year;
        $month     = $request->month ? (int) $request->month : null;

        $entries = JournalEntry::where('company_id', $companyId)
            ->where('group_code', $groupCode)
            ->where('year', $year)
            ->where('status', JournalEntryStatus::Draft)
            ->orderBy('sequence_number')
            ->get(['id', 'group_code', 'sequence_number', 'description'])
            ->map(fn ($e) => [
                'id'              => $e->id,
                'label'           => sprintf('%d-%04d — %s', $e->group_code, $e->sequence_number, $e->description),
                'sequence_number' => $e->sequence_number,
            ]);

        $suggestedId = $month
            ? $entries->firstWhere('sequence_number', $month)['id'] ?? null
            : null;

        return response()->json([
            'entries'     => $entries->values(),
            'suggestedId' => $suggestedId,
        ]);
    }

    public function index(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);

        $entries = JournalEntry::with(['creator:id,name', 'journalGroup:code,name'])
            ->withCount('lines')
            ->withSum('lines as debit_total', 'debit')
            ->withSum('lines as credit_total', 'credit')
            ->where('company_id', $companyId)
            ->orderByDesc('year')
            ->orderByDesc('group_code')
            ->orderByDesc('sequence_number')
            ->orderByDesc('id')
            ->paginate(30);

        return Inertia::render('journal-entries/Index', [
            'entries' => $entries,
        ]);
    }

    public function create(Document $document): Response
    {
        $this->authorize('create', JournalEntry::class);

        abort_unless($document->status === DocumentStatus::Verified, 403, 'Документот мора да биде верификуван.');
        abort_if($document->journalEntryLines()->exists(), 409, 'Книжење за овoj документ веќе постои.');

        $document->load(['company:id,name', 'extraction', 'lineItems.suggestedAccount', 'lineItems.suggestedKontragent']);

        [$prefillLines, $suggestedGroupCode] = $this->buildPrefillLines($document);

        // Pre-fill description based on document type
        $defaultDescription = match ($document->type) {
            \App\Enums\DocumentType::BankStatement => 'Извод ' . ($document->extraction?->vendor_name ?? $document->original_filename),
            \App\Enums\DocumentType::InvoiceIn     => 'Фактура — ' . ($document->extraction?->vendor_name ?? ''),
            \App\Enums\DocumentType::InvoiceOut    => 'Фактура — ' . ($document->extraction?->vendor_name ?? ''),
            default                                => $document->extraction?->vendor_name ?? $document->original_filename,
        };

        $accounts = ChartOfAccount::where('allows_posting', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name', 'class'])
            ->groupBy('class');

        $journalGroups = JournalGroup::orderBy('code')->get(['code', 'name']);

        return Inertia::render('journal-entries/Create', [
            'document'            => $document,
            'accounts'            => $accounts,
            'prefillLines'        => $prefillLines,
            'journalGroups'       => $journalGroups,
            'suggestedGroupCode'  => $suggestedGroupCode,
            'defaultDescription'  => $defaultDescription,
            'suggestedKontragent' => $this->suggestKontragent($document),
            'currentYear'         => (int) session('current_year', date('Y')),
        ]);
    }

    /**
     * Suggest a partner for InvoiceIn/InvoiceOut documents by matching the
     * AI-extracted tax id (exact) or name (fuzzy) against this company's kontragenti.
     * Тамара confirms/changes the suggestion manually before saving.
     */
    private function suggestKontragent(Document $document): ?array
    {
        if (! in_array($document->type, [\App\Enums\DocumentType::InvoiceIn, \App\Enums\DocumentType::InvoiceOut], true)) {
            return null;
        }

        $ext = $document->extraction;
        if (! $ext) {
            return null;
        }

        $name  = $document->type === \App\Enums\DocumentType::InvoiceIn ? $ext->vendor_name : $ext->customer_name;
        $taxId = $document->type === \App\Enums\DocumentType::InvoiceIn ? $ext->vendor_tax_id : $ext->customer_tax_id;

        $query = Kontragent::where('company_id', $document->company_id);

        $match = null;
        if ($taxId) {
            $match = (clone $query)->where('edb', $taxId)->first();
        }
        if (! $match && $name) {
            $match = (clone $query)->where('name', 'like', "%{$name}%")->first();
        }

        return $match ? ['id' => $match->id, 'name' => $match->name] : null;
    }

    private function buildPrefillLines(Document $document): array
    {
        $ext = $document->extraction;

        // ── Банкарски извод ─────────────────────────────────────────────────
        if ($document->type === \App\Enums\DocumentType::BankStatement) {
            $bankAccount = '100'; // Парични средства на трансакциски сметки во денари

            $lineItems = $document->lineItems
                ->filter(fn ($i) => (float)($i->debit ?? 0) > 0 || (float)($i->credit ?? 0) > 0);

            if ($lineItems->isNotEmpty()) {
                $lines = [];
                $sort  = 0;
                foreach ($lineItems as $item) {
                    $counter    = $item->confirmed_account_code ?? $item->suggested_account_code ?? '449';
                    $desc       = $item->description ?? '';
                    $ref        = $item->reference ? " [{$item->reference}]" : '';
                    $debitAmt   = (float)($item->debit  ?? 0);
                    $creditAmt  = (float)($item->credit ?? 0);
                    $kontragent     = $item->suggested_kontragent_id;
                    $kontragentName = $item->suggestedKontragent?->name;
                    $closingRef     = $item->suggested_closing_reference;

                    if ($creditAmt > 0) {
                        // Пари влегуваат → ДОЛЖИ 100, ПОБАРУВА контрапартија (+ AI-предложена фирма/затворање)
                        $lines[] = ['account_code' => $bankAccount, 'description' => $desc . $ref, 'debit' => $creditAmt, 'credit' => 0, 'sort_order' => $sort++];
                        $lines[] = ['account_code' => $counter,     'description' => $desc . $ref, 'debit' => 0, 'credit' => $creditAmt, 'sort_order' => $sort++, 'kontragent_id' => $kontragent, 'kontragent_name' => $kontragentName, 'closing_reference' => $closingRef];
                    } elseif ($debitAmt > 0) {
                        // Пари излегуваат → ДОЛЖИ контрапартија (+ AI-предложена фирма/затворање), ПОБАРУВА 100
                        $lines[] = ['account_code' => $counter,     'description' => $desc . $ref, 'debit' => $debitAmt, 'credit' => 0, 'sort_order' => $sort++, 'kontragent_id' => $kontragent, 'kontragent_name' => $kontragentName, 'closing_reference' => $closingRef];
                        $lines[] = ['account_code' => $bankAccount, 'description' => $desc . $ref, 'debit' => 0, 'credit' => $debitAmt, 'sort_order' => $sort++];
                    }
                }
                if (!empty($lines)) {
                    return [$lines, 10];
                }
            }

            // Fallback: користи total_debit/total_credit од extraction ако нема line items
            if ($ext && ((float)($ext->total_debit ?? 0) > 0 || (float)($ext->total_credit ?? 0) > 0)) {
                $lines = [];
                if ((float)($ext->total_credit ?? 0) > 0) {
                    $lines[] = ['account_code' => $bankAccount, 'description' => 'Вкупно примено', 'debit' => (float)$ext->total_credit, 'credit' => 0];
                    $lines[] = ['account_code' => '120',        'description' => 'Побарувања',     'debit' => 0, 'credit' => (float)$ext->total_credit];
                }
                if ((float)($ext->total_debit ?? 0) > 0) {
                    $lines[] = ['account_code' => '220',        'description' => 'Вкупно платено', 'debit' => (float)$ext->total_debit, 'credit' => 0];
                    $lines[] = ['account_code' => $bankAccount, 'description' => 'Платено',        'debit' => 0, 'credit' => (float)$ext->total_debit];
                }
                return [$lines, 10];
            }

            // Ultimate fallback — empty template
            return [[
                ['account_code' => $bankAccount, 'description' => 'Денарска сметка', 'debit' => 0, 'credit' => 0],
                ['account_code' => '449',         'description' => 'Контрапартија',   'debit' => 0, 'credit' => 0],
            ], 10];
        }

        // ── Сè друго (не фактура) → AI предлози без износ ──────────────────
        if (! $ext || ! in_array($document->type, [
            \App\Enums\DocumentType::InvoiceIn,
            \App\Enums\DocumentType::InvoiceOut,
        ])) {
            $lines = $document->lineItems
                ->filter(fn ($item) => $item->confirmed_account_code || $item->suggested_account_code)
                ->map(fn ($item) => [
                    'account_code' => $item->confirmed_account_code ?? $item->suggested_account_code,
                    'description'  => $item->description,
                    'debit'        => 0,
                    'credit'       => 0,
                ])
                ->values()
                ->all();

            return [$lines, null];
        }

        $subtotal    = round((float) ($ext->subtotal    ?? 0), 2);
        $vatAmount   = round((float) ($ext->vat_amount  ?? 0), 2);
        $totalAmount = round((float) ($ext->total_amount ?? $subtotal + $vatAmount), 2);
        $vendor      = $ext->vendor_name ?? '';

        if ($document->type === \App\Enums\DocumentType::InvoiceIn) {
            $lines = [
                // ДОЛЖИ: Трошок (449 — Останати трошоци) = Основица
                ['account_code' => '449', 'description' => "Основица — {$vendor} [Потребна проверка на сметка]", 'debit' => $subtotal,    'credit' => 0],
                // ДОЛЖИ: Влезен ДДВ (130 — ДДВ побарување) = ДДВ
                ['account_code' => '130', 'description' => "Влезен ДДВ — {$vendor}",  'debit' => $vatAmount,   'credit' => 0],
                // ПОБАРУВА: Обврски кон добавувачи (220) = Вкупно
                ['account_code' => '220', 'description' => "Обврска — {$vendor}",      'debit' => 0,            'credit' => $totalAmount],
            ];
            return [$lines, 20];
        }

        // InvoiceOut
        $lines = [
            // ДОЛЖИ: Побарувања од купувачи (120) = Вкупно
            ['account_code' => '120', 'description' => "Побарување — {$vendor}", 'debit' => $totalAmount, 'credit' => 0],
            // ПОБАРУВА: Приходи за продадени производи и услуги (740) = Основица
            ['account_code' => '740', 'description' => "Приход — {$vendor}",     'debit' => 0,            'credit' => $subtotal],
            // ПОБАРУВА: Обврски за ДДВ (230) = ДДВ
            ['account_code' => '230', 'description' => "Излезен ДДВ — {$vendor}", 'debit' => 0,           'credit' => $vatAmount],
        ];
        return [$lines, 30];
    }

    public function store(StoreJournalEntryRequest $request, Document $document): RedirectResponse
    {
        abort_unless($document->status === DocumentStatus::Verified, 403);
        abort_if($document->journalEntryLines()->exists(), 409);

        $groupCode = $request->group_code !== null ? (int) $request->group_code : null;

        $entry = DB::transaction(function () use ($request, $document, $groupCode) {
            if ($groupCode === null) {
                // Без група — секогаш засебен налог, точно еден документ
                $entry = JournalEntry::create([
                    'document_id'     => $document->id,
                    'company_id'      => $document->company_id,
                    'group_code'      => null,
                    'year'            => null,
                    'sequence_number' => null,
                    'entry_date'      => $request->entry_date,
                    'description'     => $request->description,
                    'reference'       => $request->reference,
                    'status'          => JournalEntryStatus::Draft,
                    'created_by'      => $request->user()->id,
                ]);
            } elseif ($request->sequence_number !== null) {
                // Рачно избран број на налог — најди го или создади (не дуплирај)
                $entry = MonthlyJournalResolver::resolveExplicit(
                    $document->company_id, $groupCode, (int) $request->sequence_number,
                    $request->entry_date, $request->description, $request->user()->id
                );
            } elseif (in_array($groupCode, [20, 30], true)) {
                // Влезни/Излезни фактури — едно месечно налог по група (исто како Sales/PurchaseInvoiceController)
                $entry = MonthlyJournalResolver::resolve(
                    $document->company_id, $groupCode, $request->entry_date, $request->description, $request->user()->id
                );
            } else {
                $year = (int) date('Y', strtotime($request->entry_date));
                $seq  = $this->nextSequence($groupCode, $year, $document->company_id);
                $entry = JournalEntry::create([
                    'document_id'     => $document->id,
                    'company_id'      => $document->company_id,
                    'group_code'      => $groupCode,
                    'year'            => $year,
                    'sequence_number' => $seq,
                    'entry_date'      => $request->entry_date,
                    'description'     => $request->description,
                    'reference'       => $request->reference,
                    'status'          => JournalEntryStatus::Draft,
                    'created_by'      => $request->user()->id,
                ]);
            }

            $sort = $entry->lines()->count();
            foreach ($request->lines as $line) {
                $entry->lines()->create([
                    'document_id'       => $document->id,
                    'sort_order'        => $sort++,
                    'account_code'      => $line['account_code'],
                    'kontragent_id'     => $line['kontragent_id'] ?? $request->kontragent_id,
                    'closing_reference' => $line['closing_reference'] ?? null,
                    'debit'             => $line['debit'],
                    'credit'            => $line['credit'],
                    'description'       => $line['description'] ?? null,
                ]);
            }

            $this->createLinkedMaterialInvoice($document, $request);

            return $entry;
        });

        if ($request->boolean('post_immediately')) {
            $this->doPost($entry, $document, $request->user()->id);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Книжењето е прокнижено.']);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Нацртот е зачуван.']);
        }

        return to_route('journal-entries.show', $entry);
    }

    /**
     * Кога се книжи прикачена влезна/излезна фактура преку AI-патеката, покрај
     * книговодственото налог, создади и структуриран запис во Материјално
     * (PurchaseInvoice/SalesInvoice) за да се гледа во тој дел на порталот.
     * Без поврзување со Item/Магацин — намерно нема стоковно движење (види договор).
     */
    private function createLinkedMaterialInvoice(Document $document, Request $request): void
    {
        if (! in_array($document->type, [DocumentType::InvoiceIn, DocumentType::InvoiceOut], true)) {
            return;
        }

        $ext = $document->extraction;
        if (! $ext) {
            return;
        }

        $kontragentId = $request->kontragent_id;
        $invoiceDate  = $ext->document_date ?? $request->entry_date;
        $invoiceNumber = $ext->document_number ?? $request->reference ?? ('AI-' . $document->id);

        $document->loadMissing('lineItems');
        $lineItems = $document->lineItems;

        if ($lineItems->isEmpty()) {
            // Нема детектирани ставки — една генерична ставка колку да се сочуваат вкупните износи
            $subtotal = (float) ($ext->subtotal ?? 0);
            $vatAmount = (float) ($ext->vat_amount ?? 0);
            $vatRate = $subtotal > 0 ? round($vatAmount / $subtotal * 100, 2) : 0;

            $lineItems = collect([(object) [
                'description'  => $request->description,
                'quantity'     => 1,
                'unit'         => 'бр',
                'unit_price'   => $subtotal,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total_amount' => (float) ($ext->total_amount ?? 0),
            ]]);
        }

        if ($document->type === DocumentType::InvoiceIn) {
            $invoice = PurchaseInvoice::create([
                'company_id'     => $document->company_id,
                'kontragent_id'  => $kontragentId,
                'document_id'    => $document->id,
                'supplier_name'  => $kontragentId ? null : $ext->vendor_name,
                'invoice_number' => $invoiceNumber,
                'date'           => $invoiceDate,
                'due_date'       => $ext->due_date,
                'subtotal'       => $ext->subtotal ?? 0,
                'vat_total'      => $ext->vat_amount ?? 0,
                'total_amount'   => $ext->total_amount ?? 0,
                'status'         => 'booked',
                'created_by'     => $request->user()->id,
            ]);
        } else {
            $invoice = SalesInvoice::create([
                'company_id'     => $document->company_id,
                'document_id'    => $document->id,
                'kontragent_id'  => $kontragentId,
                'client_name'    => $kontragentId ? null : $ext->customer_name,
                'invoice_number' => $invoiceNumber,
                'date'           => $invoiceDate,
                'due_date'       => $ext->due_date,
                'subtotal'       => $ext->subtotal ?? 0,
                'vat_total'      => $ext->vat_amount ?? 0,
                'total_amount'   => $ext->total_amount ?? 0,
                'status'         => 'booked',
                'created_by'     => $request->user()->id,
            ]);
        }

        foreach ($lineItems as $i => $item) {
            $totalIncVat = (float) ($item->total_amount ?? 0);
            $vatAmount   = (float) ($item->vat_amount ?? 0);

            $invoice->lines()->create([
                'item_id'            => null,
                'description'        => $item->description ?? '',
                'quantity'           => $item->quantity ?? 1,
                'unit'               => $item->unit ?? 'бр',
                'unit_price'         => $item->unit_price ?? 0,
                'vat_rate'           => $item->vat_rate ?? 0,
                'line_total_ex_vat'  => round($totalIncVat - $vatAmount, 2),
                'vat_amount'         => $vatAmount,
                'line_total_inc_vat' => $totalIncVat,
                'sort_order'         => $i,
            ]);
        }
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $this->authorize('view', $journalEntry);

        $journalEntry->load([
            'company:id,name',
            'document:id,original_filename',
            'creator:id,name',
            'poster:id,name',
            'journalGroup:code,name',
            'lines.account:code,name',
        ]);

        return Inertia::render('journal-entries/Show', [
            'entry' => $journalEntry,
        ]);
    }

    public function post(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->authorize('post', $journalEntry);

        $this->doPost($journalEntry, $journalEntry->document, $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Книжењето е прокнижено.']);

        return to_route('journal-entries.show', $journalEntry);
    }

    private function nextSequence(int $groupCode, int $year, int $companyId): int
    {
        return (JournalEntry::where('group_code', $groupCode)
            ->where('year', $year)
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->max('sequence_number') ?? 0) + 1;
    }

    public function destroy(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $companyId = $this->currentCompanyId($request);
        abort_unless($journalEntry->company_id === $companyId, 403);

        if ($journalEntry->status === JournalEntryStatus::Posted && ! $request->user()->hasRole('admin')) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Прокнижен налог не може да се избрише.']);
            return back();
        }

        $journalEntry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Налогот е избришан.']);

        return to_route('journal-entries.index');
    }

    private function doPost(JournalEntry $entry, ?Document $document, int $userId): void
    {
        $entry->update([
            'status'    => JournalEntryStatus::Posted,
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        $document?->update(['status' => DocumentStatus::Booked]);
    }
}