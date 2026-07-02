<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Warehouse::with('company:id,name')
            ->where('company_id', $this->currentCompanyId($request))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->latest();

        $warehouses = $query->paginate(20)->withQueryString();

        return Inertia::render('warehouses/Index', [
            'warehouses' => $warehouses,
            'filters'    => $request->only(['search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $companyId = $this->currentCompanyId($request);

        return Inertia::render('warehouses/Create', [
            'companies' => Company::where('id', $companyId)->get(['id', 'name']),
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

    public function inventory(Request $request, Warehouse $warehouse): Response
    {
        // Лагер листа: тековни залихи per артикл
        $inventory = WarehouseMovement::where('warehouse_id', $warehouse->id)
            ->with('item:id,code,name,unit,vat_category')
            ->select(
                'item_id',
                DB::raw("SUM(CASE WHEN movement_type IN ('in','initial') THEN quantity ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as total_out"),
                DB::raw("SUM(CASE
                    WHEN movement_type IN ('in','initial') THEN quantity
                    WHEN movement_type = 'out' THEN -quantity
                    WHEN movement_type = 'adjustment' THEN quantity
                    ELSE 0
                END) as current_stock"),
                DB::raw('AVG(CASE WHEN unit_price IS NOT NULL THEN unit_price END) as avg_price')
            )
            ->groupBy('item_id')
            ->having('current_stock', '>', 0)
            ->get();

        // Последните движења
        $movements = WarehouseMovement::where('warehouse_id', $warehouse->id)
            ->with('item:id,code,name,unit', 'creator:id,name')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $items = Item::where('company_id', $warehouse->company_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'unit', 'price_without_vat']);

        return Inertia::render('warehouses/Show', [
            'warehouse' => $warehouse->load('company:id,name'),
            'inventory' => $inventory,
            'movements' => $movements,
            'items'     => $items,
        ]);
    }
}
