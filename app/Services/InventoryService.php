<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Adjust stock by a delta (positive = in, negative = out).
     * Creates an inventory row if missing and logs a movement atomically.
     *
     * @throws ValidationException when resulting quantity would be negative and $allowNegative is false
     */
    public function adjust(
        int $productId,
        int $warehouseId,
        int $delta,
        ?int $userId = null,
        ?string $reason = null,
        mixed $reference = null,
        bool $allowNegative = false,
        ?int $lowStockThreshold = null
    ): Inventory {
        return DB::transaction(function () use ($productId, $warehouseId, $delta, $userId, $reason, $reference, $allowNegative, $lowStockThreshold) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = new Inventory([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'low_stock_threshold' => $lowStockThreshold
                        ?? (int) config('stockly.low_stock_default', 5),
                ]);
            }

            $newQty = $inventory->quantity + $delta;

            if ($newQty < 0 && ! $allowNegative) {
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock for product #{$productId} in warehouse #{$warehouseId} (have {$inventory->quantity}, need ".abs($delta).').'],
                ]);
            }

            $inventory->quantity = $newQty;
            $inventory->save();

            $type = $delta > 0 ? MovementType::In : ($delta < 0 ? MovementType::Out : MovementType::Adjustment);

            InventoryMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'quantity' => $delta,
                'balance_after' => $newQty,
                'reason' => $reason,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $userId,
            ]);

            return $inventory->fresh();
        });
    }

    /** Set absolute quantity (stocktake correction). */
    public function setQuantity(
        int $productId,
        int $warehouseId,
        int $newQuantity,
        ?int $userId = null,
        ?string $reason = 'Stocktake correction',
        mixed $reference = null
    ): Inventory {
        return DB::transaction(function () use ($productId, $warehouseId, $newQuantity, $userId, $reason, $reference) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $current = $inventory?->quantity ?? 0;
            $delta = $newQuantity - $current;

            if (! $inventory) {
                $inventory = new Inventory([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => 0,
                    'low_stock_threshold' => (int) config('stockly.low_stock_default', 5),
                ]);
            }

            $inventory->quantity = $newQuantity;
            $inventory->save();

            InventoryMovement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => MovementType::Adjustment,
                'quantity' => $delta,
                'balance_after' => $newQuantity,
                'reason' => $reason,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $userId,
            ]);

            return $inventory->fresh();
        });
    }

    /** Transfer stock between warehouses atomically. */
    public function transfer(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        ?int $userId = null,
        ?string $reason = null
    ): void {
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => ['Transfer quantity must be positive.']]);
        }
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages(['warehouse_id' => ['Source and destination must differ.']]);
        }

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $userId, $reason) {
            $this->adjust($productId, $fromWarehouseId, -$quantity, $userId, $reason ?? "Transfer to warehouse #{$toWarehouseId}");
            $this->adjust($productId, $toWarehouseId, $quantity, $userId, $reason ?? "Transfer from warehouse #{$fromWarehouseId}");
        });
    }
}
