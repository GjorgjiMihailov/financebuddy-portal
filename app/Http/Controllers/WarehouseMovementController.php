<?php

namespace App\Http\Controllers;

use App\Models\WarehouseMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseMovementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id'  => ['required', 'exists:warehouses,id'],
            'item_id'       => ['required', 'exists:items,id'],
            'movement_type' => ['required', 'in:in,out,adjustment,initial'],
            'quantity'      => ['required', 'numeric', 'min:0.001'],
            'unit_price'    => ['nullable', 'numeric', 'min:0'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'movement_date' => ['required', 'date'],
        ]);

        WarehouseMovement::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Движењето е зачувано.']);

        return back();
    }
}
