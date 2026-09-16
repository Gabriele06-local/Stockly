<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Create an order with items inside a DB transaction and decrement stock.
     *
     * $data: ['customer_id','warehouse_id','user_id','status','tax_rate','discount_amount','notes','items'=>[['product_id','quantity','unit_price'?]]]
     *
     * @throws ValidationException on validation / insufficient stock
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = (int) $data['warehouse_id'];
            $items = $data['items'] ?? [];

            if (empty($items)) {
                throw ValidationException::withMessages(['items' => ['Order must contain at least one item.']]);
            }

            $subtotal = 0;
            $lines = [];

            foreach ($items as $row) {
                $product = Product::active()->lockForUpdate()->find($row['product_id'] ?? null);
                if (! $product) {
                    throw ValidationException::withMessages(['items' => ['Product #'.($row['product_id'] ?? '?').' not found or inactive.']]);
                }

                $qty = (int) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['items' => ["Invalid quantity for {$product->name}."]]);
                }

                $unitPrice = isset($row['unit_price']) ? (float) $row['unit_price'] : (float) $product->price;
                if ($unitPrice < 0) {
                    throw ValidationException::withMessages(['items' => ["Invalid price for {$product->name}."]]);
                }

                $lineTotal = round($qty * $unitPrice, 2);
                $subtotal += $lineTotal;
                $lines[] = compact('product', 'qty', 'unitPrice', 'lineTotal');
            }

            $discount = round((float) ($data['discount_amount'] ?? 0), 2);
            if ($discount < 0 || $discount > $subtotal) {
                throw ValidationException::withMessages(['discount_amount' => ['Invalid discount amount.']]);
            }

            $taxRate = (float) ($data['tax_rate'] ?? config('stockly.tax_rate', 0.22));
            $taxable = $subtotal - $discount;
            $tax = round($taxable * $taxRate, 2);
            $total = round($taxable + $tax, 2);

            $statusRaw = $data['status'] ?? OrderStatus::Confirmed->value;
            $status = $statusRaw instanceof OrderStatus
                ? $statusRaw
                : (OrderStatus::tryFrom((string) $statusRaw) ?? OrderStatus::Confirmed);

            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'warehouse_id' => $warehouseId,
                'user_id' => $data['user_id'] ?? null,
                'status' => $status,
                'subtotal' => round($subtotal, 2),
                'tax_rate' => $taxRate,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            // Reserve stock only for non-draft/non-cancelled orders
            $shouldConsume = in_array($status, OrderStatus::stockAffecting(), true);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unitPrice'],
                    'total' => $line['lineTotal'],
                ]);

                if ($shouldConsume) {
                    $this->inventory->adjust(
                        $line['product']->id,
                        $warehouseId,
                        -$line['qty'],
                        $data['user_id'] ?? null,
                        'Sale '.$order->order_number,
                        $order
                    );
                }
            }

            $order->customer()->increment('total_spent', $total);

            return $order->fresh(['items.product', 'customer', 'warehouse']);
        });
    }

    /** Cancel an order and restore stock if it had consumed stock. */
    public function cancel(Order $order, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::Cancelled) {
                return $order;
            }

            $wasConsuming = in_array($order->status, OrderStatus::stockAffecting(), true);

            if ($wasConsuming) {
                foreach ($order->items as $item) {
                    $this->inventory->adjust(
                        $item->product_id,
                        $order->warehouse_id,
                        $item->quantity,
                        $userId,
                        'Cancel '.$order->order_number,
                        $order
                    );
                }
                $order->customer()->decrement('total_spent', (float) $order->total);
            }

            $order->status = OrderStatus::Cancelled;
            $order->save();

            return $order->fresh(['items.product', 'customer']);
        });
    }

    /** Update status with correct stock side-effects (draft->confirmed consumes, confirmed->cancelled restores). */
    public function updateStatus(Order $order, OrderStatus $newStatus, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $userId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $old = $order->status;

            if ($old === $newStatus) {
                return $order;
            }

            $oldConsuming = in_array($old, OrderStatus::stockAffecting(), true);
            $newConsuming = in_array($newStatus, OrderStatus::stockAffecting(), true);

            if (! $oldConsuming && $newConsuming) {
                foreach ($order->items as $item) {
                    $this->inventory->adjust($item->product_id, $order->warehouse_id, -$item->quantity, $userId, 'Sale '.$order->order_number, $order);
                }
                $order->customer()->increment('total_spent', (float) $order->total);
            } elseif ($oldConsuming && ! $newConsuming && $newStatus === OrderStatus::Cancelled) {
                foreach ($order->items as $item) {
                    $this->inventory->adjust($item->product_id, $order->warehouse_id, $item->quantity, $userId, 'Cancel '.$order->order_number, $order);
                }
                $order->customer()->decrement('total_spent', (float) $order->total);
            }

            $order->status = $newStatus;
            $order->save();

            return $order->fresh(['items.product', 'customer']);
        });
    }
}
