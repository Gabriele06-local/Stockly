<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustInventoryRequest;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(Request $request)
    {
        $query = Inventory::with(['product.category', 'warehouse'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->whereHas('product', fn ($qq) => $qq->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
            })
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('updated_at', 'desc')
            ->paginate(15)->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();

        return view('inventory.index', compact('query', 'warehouses'));
    }

    public function movements(Request $request)
    {
        $movements = InventoryMovement::with(['product', 'warehouse', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->latest()->paginate(20)->withQueryString();

        return view('inventory.movements', compact('movements'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('inventory.adjust', compact('products', 'warehouses'));
    }

    public function adjust(AdjustInventoryRequest $request)
    {
        $data = $request->validated();

        try {
            $this->inventory->adjust(
                $data['product_id'],
                $data['warehouse_id'],
                $data['quantity'],
                $request->user()->id,
                $data['reason'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stock adjusted.']);
        }

        return redirect()->route('inventory.index')->with('success', 'Stock adjusted.');
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->authorize('transfer', Inventory::class);

        try {
            $this->inventory->transfer(
                $validated['product_id'],
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['quantity'],
                $request->user()->id,
                $validated['reason'] ?? null
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('inventory.index')->with('success', 'Stock transferred.');
    }
}
