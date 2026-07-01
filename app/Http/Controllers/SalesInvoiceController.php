<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SalesInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesInvoice::with('company:id,name', 'creator:id,name')
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('date');

        $invoices = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('sales-invoices/Index', [
            'invoices'  => $invoices,
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('sales-invoices/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'     => ['required', 'exists:companies,id'],
            'client_name'    => ['required', 'string', 'max:255'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'date'           => ['required', 'date'],
            'total_amount'   => ['required', 'numeric', 'min:0'],
        ]);

        SalesInvoice::create([
            ...$validated,
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Излезната фактура е зачувана.']);

        return to_route('sales-invoices.index');
    }
}
