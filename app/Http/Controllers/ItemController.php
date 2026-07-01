<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Item::with('company:id,name')
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%");
            }))
            ->orderBy('code');

        $items = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('items/Index', [
            'items'     => $items,
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('items/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'       => ['required', 'exists:companies,id'],
            'code'             => ['required', 'string', 'max:50'],
            'name'             => ['required', 'string', 'max:255'],
            'unit'             => ['required', 'string', 'max:20'],
            'vat_category'     => ['required', 'in:0,5,18'],
            'price_without_vat'=> ['required', 'numeric', 'min:0'],
            'is_active'        => ['boolean'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        Item::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Артиклот е успешно додаден.']);

        return to_route('items.index');
    }

    public function edit(Item $item): Response
    {
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('items/Edit', [
            'item'      => $item,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'unit'             => ['required', 'string', 'max:20'],
            'vat_category'     => ['required', 'in:0,5,18'],
            'price_without_vat'=> ['required', 'numeric', 'min:0'],
            'is_active'        => ['boolean'],
        ]);

        $item->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Артиклот е ажуриран.']);

        return to_route('items.index');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Артиклот е избришан.']);

        return to_route('items.index');
    }
}
