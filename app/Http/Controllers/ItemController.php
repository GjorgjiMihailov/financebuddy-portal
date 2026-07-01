<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $stockSub = "(SELECT COALESCE(SUM(CASE
                WHEN movement_type IN ('in','initial') THEN quantity
                WHEN movement_type = 'out' THEN -quantity
                WHEN movement_type = 'adjustment' THEN quantity
                ELSE 0 END), 0)
            FROM warehouse_movements WHERE item_id = items.id) as current_stock";

        $query = Item::with('company:id,name')
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%");
            }))
            ->selectRaw("items.*, {$stockSub}")
            ->orderBy('code');

        $items     = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);

        return Inertia::render('items/Index', [
            'items'     => $items,
            'companies' => $companies,
            'filters'   => $request->only(['company_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $companies  = Company::orderBy('name')->get(['id', 'name']);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'company_id', 'name']);

        return Inertia::render('items/Create', [
            'companies'  => $companies,
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id'           => ['required', 'exists:companies,id'],
            'code'                 => ['required', 'string', 'max:50'],
            'name'                 => ['required', 'string', 'max:255'],
            'unit'                 => ['required', 'string', 'max:20'],
            'vat_category'         => ['required', 'in:0,5,18'],
            'price_without_vat'    => ['required', 'numeric', 'min:0'],
            'is_active'            => ['boolean'],
            'initial_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'initial_stock'        => ['nullable', 'numeric', 'min:0.001'],
            'initial_date'         => ['nullable', 'date'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $item = Item::create(array_diff_key($validated, array_flip(['initial_warehouse_id', 'initial_stock', 'initial_date'])));

        if (!empty($validated['initial_warehouse_id']) && !empty($validated['initial_stock']) && $validated['initial_stock'] > 0) {
            WarehouseMovement::create([
                'warehouse_id'   => $validated['initial_warehouse_id'],
                'item_id'        => $item->id,
                'movement_type'  => 'initial',
                'quantity'       => $validated['initial_stock'],
                'unit_price'     => $validated['price_without_vat'],
                'reference_type' => 'manual',
                'note'           => 'Почетна залиха',
                'movement_date'  => $validated['initial_date'] ?? now()->toDateString(),
                'created_by'     => $request->user()->id,
            ]);
        }

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
            'name'              => ['required', 'string', 'max:255'],
            'unit'              => ['required', 'string', 'max:20'],
            'vat_category'      => ['required', 'in:0,5,18'],
            'price_without_vat' => ['required', 'numeric', 'min:0'],
            'is_active'         => ['boolean'],
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

    public function forCompany(Company $company): JsonResponse
    {
        $items = Item::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'unit', 'price_without_vat', 'vat_category']);

        return response()->json($items);
    }
}
