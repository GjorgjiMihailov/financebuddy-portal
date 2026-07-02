<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PurchaseInvoice::with('company:id,name', 'kontragent:id,name', 'creator:id,name')
            ->where('company_id', $this->currentCompanyId($request))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('date');

        $invoices = $query->paginate(20)->withQueryString();

        $companyId  = $this->currentCompanyId($request);
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('purchase-invoices/Index', [
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
            'supplier_name'  => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'date'           => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],
            'status'         => ['in:draft,booked'],
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['nullable', 'exists:items,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'        => ['required', 'string', 'max:20'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate'    => ['required', 'numeric', 'in:0,5,18'],
        ]);

        DB::transaction(function () use ($validated, $request) {
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

            $invoice = PurchaseInvoice::create([
                'company_id'    => $validated['company_id'],
                'warehouse_id'  => $validated['warehouse_id'] ?? null,
                'kontragent_id' => $validated['kontragent_id'] ?? null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'invoice_number'=> $validated['invoice_number'],
                'date'          => $validated['date'],
                'due_date'      => $validated['due_date'] ?? null,
                'notes'         => $validated['notes'] ?? null,
                'subtotal'      => round($subtotal, 2),
                'vat_total'     => round($vatTotal, 2),
                'total_amount'  => round($subtotal + $vatTotal, 2),
                'status'        => $validated['status'] ?? 'draft',
                'created_by'    => $request->user()->id,
            ]);

            $invoice->lines()->insert(array_map(fn ($l) => array_merge($l, ['purchase_invoice_id' => $invoice->id]), $linesData));

            if (($validated['status'] ?? 'draft') === 'booked' && ! empty($validated['warehouse_id'])) {
                $this->createStockInMovements($invoice, $request->user()->id);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Влезната фактура е зачувана.']);

        return to_route('purchase-invoices.index');
    }

    public function show(PurchaseInvoice $purchaseInvoice): Response
    {
        $purchaseInvoice->load([
            'company:id,name',
            'warehouse:id,name',
            'kontragent:id,name,edb,address',
            'creator:id,name',
            'lines.item:id,code,name,is_service',
        ]);

        return Inertia::render('purchase-invoices/Show', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    public function book(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status !== 'draft') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Само нацрт фактури можат да се книжат.']);
            return back();
        }

        DB::transaction(function () use ($purchaseInvoice, $request) {
            $purchaseInvoice->update(['status' => 'booked']);
            if ($purchaseInvoice->warehouse_id) {
                $this->createStockInMovements($purchaseInvoice, $request->user()->id);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Фактурата е книжена. Залихите се ажурирани.']);

        return to_route('purchase-invoices.show', $purchaseInvoice->id);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Само нацрт фактури можат да се уредат.']);
        }

        $validated = $request->validate([
            'warehouse_id'   => ['nullable', 'exists:warehouses,id'],
            'kontragent_id'  => ['nullable', 'exists:kontragenti,id'],
            'supplier_name'  => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'date'           => ['required', 'date'],
            'due_date'       => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*.item_id'     => ['nullable', 'exists:items,id'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'        => ['required', 'string', 'max:20'],
            'lines.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate'    => ['required', 'numeric', 'in:0,5,18'],
        ]);

        DB::transaction(function () use ($validated, $purchaseInvoice) {
            $subtotal = 0;
            $vatTotal = 0;
            $linesData = [];

            foreach ($validated['lines'] as $idx => $line) {
                $exVat  = round($line['quantity'] * $line['unit_price'], 2);
                $vat    = round($exVat * $line['vat_rate'] / 100, 2);
                $subtotal += $exVat;
                $vatTotal += $vat;

                $linesData[] = [
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'item_id'             => $line['item_id'] ?? null,
                    'description'         => $line['description'],
                    'quantity'            => $line['quantity'],
                    'unit'                => $line['unit'],
                    'unit_price'          => $line['unit_price'],
                    'vat_rate'            => $line['vat_rate'],
                    'line_total_ex_vat'   => $exVat,
                    'vat_amount'          => $vat,
                    'line_total_inc_vat'  => $exVat + $vat,
                    'sort_order'          => $idx,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];
            }

            $purchaseInvoice->update([
                'warehouse_id'  => $validated['warehouse_id'] ?? null,
                'kontragent_id' => $validated['kontragent_id'] ?? null,
                'supplier_name' => $validated['supplier_name'] ?? null,
                'invoice_number'=> $validated['invoice_number'],
                'date'          => $validated['date'],
                'due_date'      => $validated['due_date'] ?? null,
                'notes'         => $validated['notes'] ?? null,
                'subtotal'      => round($subtotal, 2),
                'vat_total'     => round($vatTotal, 2),
                'total_amount'  => round($subtotal + $vatTotal, 2),
            ]);

            $purchaseInvoice->lines()->delete();
            PurchaseInvoiceLine::insert($linesData);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Фактурата е ажурирана.']);

        return to_route('purchase-invoices.show', $purchaseInvoice->id);
    }

    public function destroy(PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Само нацрт фактури можат да се избришат.']);
        }

        $purchaseInvoice->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Фактурата е избришана.']);

        return to_route('purchase-invoices.index');
    }

    private function createStockInMovements(PurchaseInvoice $invoice, int $userId): void
    {
        $existing = WarehouseMovement::where('reference_type', 'purchase_invoice')
            ->where('reference_id', $invoice->id)
            ->exists();

        if ($existing) {
            return;
        }

        $invoice->loadMissing('lines.item');

        foreach ($invoice->lines as $line) {
            if (! $line->item_id) continue;
            $item = $line->item;
            if (! $item || $item->is_service) continue;

            WarehouseMovement::create([
                'warehouse_id'   => $invoice->warehouse_id,
                'item_id'        => $item->id,
                'movement_type'  => 'in',
                'quantity'       => $line->quantity,
                'unit_price'     => $line->unit_price,
                'reference_type' => 'purchase_invoice',
                'reference_id'   => $invoice->id,
                'movement_date'  => $invoice->date,
                'created_by'     => $userId,
            ]);
        }
    }
}
