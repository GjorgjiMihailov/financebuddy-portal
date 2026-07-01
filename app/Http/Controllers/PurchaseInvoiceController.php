<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
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
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('date');

        $invoices  = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('purchase-invoices/Index', [
            'invoices'  => $invoices,
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
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
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Влезната фактура е зачувана.']);

        return to_route('purchase-invoices.index');
    }

    public function show(PurchaseInvoice $purchaseInvoice): Response
    {
        $purchaseInvoice->load([
            'company:id,name',
            'kontragent:id,name,edb,address',
            'creator:id,name',
            'lines.item:id,code,name',
        ]);

        return Inertia::render('purchase-invoices/Show', [
            'invoice' => $purchaseInvoice,
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice): RedirectResponse
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->withErrors(['status' => 'Само нацрт фактури можат да се уредат.']);
        }

        $validated = $request->validate([
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
                    'purchase_invoice_id'=> $purchaseInvoice->id,
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

            $purchaseInvoice->update([
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
}
