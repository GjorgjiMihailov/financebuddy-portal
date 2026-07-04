<?php

namespace App\Http\Controllers;

use App\Enums\JournalEntryStatus;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesInvoice::with('company:id,name', 'kontragent:id,name', 'creator:id,name')
            ->where('company_id', $this->currentCompanyId($request))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('date');

        $invoices = $query->paginate(20)->withQueryString();

        $companyId  = $this->currentCompanyId($request);
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('sales-invoices/Index', [
            'invoices'   => $invoices,
            'warehouses' => $warehouses,
            'filters'    => $request->only(['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
            'warehouse_id'   => ['nullable', 'exists:warehouses,id'],
            'kontragent_id'  => ['nullable', 'exists:kontragenti,id'],
            'client_name'    => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'date'           => ['required', 'date'],
            'date_of_supply' => ['nullable', 'date'],
            'due_date'       => ['nullable', 'date'],
            'currency'       => ['in:MKD,EUR,USD,CHF,GBP'],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
            'status'         => ['in:draft,sent,booked'],
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['nullable', 'exists:items,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'        => ['required', 'string', 'max:20'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate'    => ['required', 'numeric', 'in:0,5,18'],
        ]);

        $warnings = [];

        DB::transaction(function () use ($validated, $request, &$warnings) {
            $subtotal = 0;
            $vatTotal = 0;
            $linesData = [];

            foreach ($validated['lines'] as $idx => $line) {
                $exVat  = round($line['quantity'] * $line['unit_price'], 2);
                $vat    = round($exVat * $line['vat_rate'] / 100, 2);
                $subtotal += $exVat;
                $vatTotal += $vat;

                $linesData[] = [
                    'item_id'            => $line['item_id'] ?? null,
                    'description'        => $line['description'],
                    'quantity'           => $line['quantity'],
                    'unit'               => $line['unit'],
                    'unit_price'         => $line['unit_price'],
                    'vat_rate'           => $line['vat_rate'],
                    'line_total_ex_vat'  => $exVat,
                    'vat_amount'         => $vat,
                    'line_total_inc_vat' => $exVat + $vat,
                    'sort_order'         => $idx,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            $invoice = SalesInvoice::create([
                'company_id'     => $validated['company_id'],
                'warehouse_id'   => $validated['warehouse_id'] ?? null,
                'kontragent_id'  => $validated['kontragent_id'] ?? null,
                'client_name'    => $validated['client_name'] ?? null,
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'date_of_supply' => $validated['date_of_supply'] ?? null,
                'due_date'       => $validated['due_date'] ?? null,
                'currency'       => $validated['currency'] ?? 'MKD',
                'exchange_rate'  => $validated['exchange_rate'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'subtotal'       => round($subtotal, 2),
                'vat_total'      => round($vatTotal, 2),
                'total_amount'   => round($subtotal + $vatTotal, 2),
                'status'         => $validated['status'] ?? 'draft',
                'created_by'     => $request->user()->id,
            ]);

            $invoice->lines()->insert(array_map(fn ($l) => array_merge($l, ['sales_invoice_id' => $invoice->id]), $linesData));

            if (($validated['status'] ?? 'draft') === 'sent' && ! empty($validated['warehouse_id'])) {
                $warnings = $this->createStockOutMovements($invoice, $request->user()->id);
            }
        });

        $message = empty($warnings) ? 'Излезната фактура е зачувана.' : 'Фактурата е зачувана. Предупредување: ' . implode('; ', $warnings);
        $type    = empty($warnings) ? 'success' : 'warning';
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return to_route('sales-invoices.index');
    }

    public function show(SalesInvoice $salesInvoice): Response
    {
        $salesInvoice->load([
            'company',
            'warehouse:id,name',
            'kontragent:id,name,edb,address',
            'creator:id,name',
            'lines.item:id,code,name,is_service',
        ]);

        return Inertia::render('sales-invoices/Show', [
            'invoice' => $salesInvoice,
        ]);
    }

    public function update(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Само нацрт фактури можат да се уредат.']);
        }

        $validated = $request->validate([
            'warehouse_id'   => ['nullable', 'exists:warehouses,id'],
            'kontragent_id'  => ['nullable', 'exists:kontragenti,id'],
            'client_name'    => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'date'           => ['required', 'date'],
            'date_of_supply' => ['nullable', 'date'],
            'due_date'       => ['nullable', 'date'],
            'currency'       => ['in:MKD,EUR,USD,CHF,GBP'],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
            'status'         => ['in:draft,sent'],
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['nullable', 'exists:items,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'        => ['required', 'string', 'max:20'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate'    => ['required', 'numeric', 'in:0,5,18'],
        ]);

        $warnings = [];

        DB::transaction(function () use ($validated, $salesInvoice, $request, &$warnings) {
            $subtotal = 0;
            $vatTotal = 0;
            $linesData = [];

            foreach ($validated['lines'] as $idx => $line) {
                $exVat  = round($line['quantity'] * $line['unit_price'], 2);
                $vat    = round($exVat * $line['vat_rate'] / 100, 2);
                $subtotal += $exVat;
                $vatTotal += $vat;

                $linesData[] = [
                    'sales_invoice_id'   => $salesInvoice->id,
                    'item_id'            => $line['item_id'] ?? null,
                    'description'        => $line['description'],
                    'quantity'           => $line['quantity'],
                    'unit'               => $line['unit'],
                    'unit_price'         => $line['unit_price'],
                    'vat_rate'           => $line['vat_rate'],
                    'line_total_ex_vat'  => $exVat,
                    'vat_amount'         => $vat,
                    'line_total_inc_vat' => $exVat + $vat,
                    'sort_order'         => $idx,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            $prevStatus = $salesInvoice->status;

            $salesInvoice->update([
                'warehouse_id'   => $validated['warehouse_id'] ?? null,
                'kontragent_id'  => $validated['kontragent_id'] ?? null,
                'client_name'    => $validated['client_name'] ?? null,
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'date_of_supply' => $validated['date_of_supply'] ?? null,
                'due_date'       => $validated['due_date'] ?? null,
                'currency'       => $validated['currency'] ?? $salesInvoice->currency,
                'exchange_rate'  => $validated['exchange_rate'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'subtotal'       => round($subtotal, 2),
                'vat_total'      => round($vatTotal, 2),
                'total_amount'   => round($subtotal + $vatTotal, 2),
                'status'         => $validated['status'] ?? $salesInvoice->status,
            ]);

            $salesInvoice->lines()->delete();
            SalesInvoiceLine::insert($linesData);

            if ($prevStatus === 'draft' && ($validated['status'] ?? '') === 'sent' && $salesInvoice->warehouse_id) {
                $warnings = $this->createStockOutMovements($salesInvoice->fresh(), $request->user()->id);
            }
        });

        $message = empty($warnings) ? 'Фактурата е ажурирана.' : 'Фактурата е ажурирана. Предупредување: ' . implode('; ', $warnings);
        $type    = empty($warnings) ? 'success' : 'warning';
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return to_route('sales-invoices.show', $salesInvoice->id);
    }

    public function send(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        if ($salesInvoice->status !== 'draft') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Само нацрт фактури можат да се испратат.']);
            return back();
        }

        $warnings = [];

        DB::transaction(function () use ($salesInvoice, $request, &$warnings) {
            $salesInvoice->update(['status' => 'sent']);
            if ($salesInvoice->warehouse_id) {
                $warnings = $this->createStockOutMovements($salesInvoice, $request->user()->id);
            }
        });

        $message = empty($warnings) ? 'Фактурата е испратена.' : 'Фактурата е испратена. Предупредување: ' . implode('; ', $warnings);
        $type    = empty($warnings) ? 'success' : 'warning';
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return to_route('sales-invoices.show', $salesInvoice->id);
    }

    public function book(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        if ($salesInvoice->status !== 'sent') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Само испратени фактури можат да се книжат.']);
            return back();
        }

        $targetEntryId = $request->input('journal_entry_id') ? (int) $request->input('journal_entry_id') : null;
        $voucherNumber = $request->input('voucher_number') ? trim($request->input('voucher_number')) : null;

        DB::transaction(function () use ($salesInvoice, $request, $targetEntryId, $voucherNumber) {
            $salesInvoice->update(['status' => 'booked']);
            $this->addToMonthlyJournal($salesInvoice, $request->user()->id, $targetEntryId, $voucherNumber);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Фактурата е книжена. Налогот е ажуриран.']);

        return to_route('sales-invoices.show', $salesInvoice->id);
    }

    private function addToMonthlyJournal(SalesInvoice $invoice, int $userId, ?int $targetEntryId = null, ?string $voucherNumber = null): void
    {
        $date      = $invoice->date;
        $year      = (int) date('Y', strtotime($date));
        $month     = (int) date('n', strtotime($date));
        $monthName = ['Јануари','Февруари','Март','Април','Мај','Јуни',
                      'Јули','Август','Септември','Октомври','Ноември','Декември'][$month - 1];

        if ($targetEntryId) {
            $entry = JournalEntry::lockForUpdate()->findOrFail($targetEntryId);
        } elseif ($voucherNumber && preg_match('/^(\d+)-(\d+)$/', $voucherNumber, $m)) {
            $vGroupCode = (int) $m[1];
            $vSeqNum    = (int) $m[2];
            $entry = JournalEntry::where('company_id', $invoice->company_id)
                ->where('group_code', $vGroupCode)
                ->where('year', $year)
                ->where('sequence_number', $vSeqNum)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                $entry = JournalEntry::create([
                    'company_id'      => $invoice->company_id,
                    'group_code'      => $vGroupCode,
                    'year'            => $year,
                    'sequence_number' => $vSeqNum,
                    'entry_date'      => $date,
                    'description'     => "Излезни фактури – {$monthName} {$year}",
                    'status'          => JournalEntryStatus::Draft,
                    'created_by'      => $userId,
                ]);
            }
        } else {
            $entry = JournalEntry::where('company_id', $invoice->company_id)
                ->where('group_code', 30)
                ->where('year', $year)
                ->where('sequence_number', $month)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                $entry = JournalEntry::create([
                    'company_id'      => $invoice->company_id,
                    'group_code'      => 30,
                    'year'            => $year,
                    'sequence_number' => $month,
                    'entry_date'      => $date,
                    'description'     => "Излезни фактури – {$monthName} {$year}",
                    'status'          => JournalEntryStatus::Draft,
                    'created_by'      => $userId,
                ]);
            }
        }

        $invoice->loadMissing(['kontragent', 'lines.item']);
        $partner   = $invoice->kontragent?->name ?? $invoice->client_name ?? '—';
        $ref       = "{$invoice->invoice_number} – {$partner}";
        $sort      = $entry->lines()->count();

        // VAT amounts grouped by rate (skip 0%)
        $vatByRate = [];
        foreach ($invoice->lines as $line) {
            $rate = (int) $line->vat_rate;
            if ($rate > 0) {
                $vatByRate[$rate] = round(($vatByRate[$rate] ?? 0) + (float) $line->vat_amount, 2);
            }
        }

        // Detect revenue account: 620 (услуги) or 630 (стока)
        $hasGoods    = $invoice->lines->contains(fn ($l) => $l->item && ! $l->item->is_service);
        $hasServices = $invoice->lines->contains(fn ($l) => $l->item && $l->item->is_service);
        if ($hasServices && ! $hasGoods) {
            $revenueAccount = '620'; // Приходи од услуги — домашни
        } else {
            $revenueAccount = '630'; // Приходи од продажба на стока — домашна
        }

        // ДОЛЖИ: Побарувања од купувачи (200) = Вкупно
        $entry->lines()->create([
            'sort_order'    => $sort++,
            'account_code'  => '200',
            'kontragent_id' => $invoice->kontragent_id,
            'line_date'     => $date,
            'description'   => $ref,
            'debit'         => (float) $invoice->total_amount,
            'credit'        => 0,
        ]);

        // ПОБАРУВА: Приходи (620/630) = Основица
        $entry->lines()->create([
            'sort_order'    => $sort++,
            'account_code'  => $revenueAccount,
            'kontragent_id' => $invoice->kontragent_id,
            'line_date'     => $date,
            'description'   => $ref,
            'debit'         => 0,
            'credit'        => (float) $invoice->subtotal,
        ]);

        // ПОБАРУВА: Обврски за ДДВ (450) по стапка
        foreach ($vatByRate as $rate => $vatAmount) {
            $entry->lines()->create([
                'sort_order'    => $sort++,
                'account_code'  => '450',
                'kontragent_id' => $invoice->kontragent_id,
                'line_date'     => $date,
                'description'   => "{$ref} / ДДВ {$rate}%",
                'debit'         => 0,
                'credit'        => $vatAmount,
            ]);
        }
    }

    public function destroy(Request $request, SalesInvoice $salesInvoice): RedirectResponse
    {
        if ($salesInvoice->status !== 'draft' && ! $request->user()->hasRole('admin')) {
            return back()->withErrors(['status' => 'Само нацрт фактури можат да се избришат.']);
        }

        $salesInvoice->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Фактурата е избришана.']);

        return to_route('sales-invoices.index');
    }

    private function createStockOutMovements(SalesInvoice $invoice, int $userId): array
    {
        $existing = WarehouseMovement::where('reference_type', 'sales_invoice')
            ->where('reference_id', $invoice->id)
            ->exists();

        if ($existing) {
            return [];
        }

        $warnings  = [];
        $invoice->loadMissing('lines.item');

        foreach ($invoice->lines as $line) {
            if (! $line->item_id) continue;
            $item = $line->item;
            if (! $item || $item->is_service) continue;

            $stock = (float) WarehouseMovement::where('warehouse_id', $invoice->warehouse_id)
                ->where('item_id', $item->id)
                ->selectRaw("COALESCE(SUM(CASE
                    WHEN movement_type IN ('in','initial') THEN quantity
                    WHEN movement_type = 'out' THEN -quantity
                    WHEN movement_type = 'adjustment' THEN quantity
                    ELSE 0 END), 0) as total")
                ->value('total');

            if ($stock < $line->quantity) {
                $warnings[] = "{$item->name}: залиха {$stock}, потребно {$line->quantity}";
            }

            WarehouseMovement::create([
                'warehouse_id'   => $invoice->warehouse_id,
                'item_id'        => $item->id,
                'movement_type'  => 'out',
                'quantity'       => $line->quantity,
                'unit_price'     => $line->unit_price,
                'reference_type' => 'sales_invoice',
                'reference_id'   => $invoice->id,
                'movement_date'  => $invoice->date,
                'created_by'     => $userId,
            ]);
        }

        return $warnings;
    }
}
