<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    public function index(Request $request)
    {
        $items = Inventory::with(['product', 'warehouse'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->paginate($request->integer('per_page', 15));

        return InventoryResource::collection($items);
    }

    public function lowStock(Request $request)
    {
        $items = Inventory::with(['product', 'warehouse'])
            ->lowStock()
            ->orderBy('quantity')
            ->paginate($request->integer('per_page', 15));

        return InventoryResource::collection($items);
    }

    public function movements(Request $request)
    {
        $movements = InventoryMovement::with(['product', 'warehouse'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->latest()->paginate($request->integer('per_page', 20));

        return response()->json($movements);
    }

    public function adjust(\App\Http\Requests\AdjustInventoryRequest $request)
    {
        $data = $request->validated();

        $inv = $this->inventory->adjust(
            $data['product_id'], $data['warehouse_id'], $data['quantity'],
            $request->user()->id, $data['reason'] ?? null
        );

        return new InventoryResource($inv->load(['product', 'warehouse']));
    }
}
