<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Warehouse::with('company:id,name')
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest();

        $warehouses = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('warehouses/Index', [
            'warehouses' => $warehouses,
            'companies'  => $companies,
            'filters'    => $request->only(['company_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('warehouses/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name'       => ['required', 'string', 'max:255'],
            'location'   => ['nullable', 'string', 'max:255'],
            'is_active'  => ['boolean'],
        ]);

        Warehouse::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Магацинот е успешно додаден.']);

        return to_route('warehouses.index');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'location'  => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $warehouse->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Магацинот е ажуриран.']);

        return to_route('warehouses.index');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Магацинот е избришан.']);

        return to_route('warehouses.index');
    }
}
