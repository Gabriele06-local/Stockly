<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Warehouse::class);
        $warehouses = Warehouse::withSum('inventories', 'quantity')
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.create');
    }

    public function store(StoreWarehouseRequest $request)
    {
        $data = $request->validated();
        if (! empty($data['is_default'])) {
            Warehouse::query()->update(['is_default' => false]);
        }
        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created.');
    }

    public function edit(Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $data = $request->validated();
        if (! empty($data['is_default'])) {
            Warehouse::where('id', '!=', $warehouse->id)->update(['is_default' => false]);
        }
        $warehouse->update($data);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        if ($warehouse->inventories()->where('quantity', '>', 0)->exists() || $warehouse->orders()->exists()) {
            return back()->with('error', 'Cannot delete warehouse with stock or orders.');
        }
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted.');
    }
}
