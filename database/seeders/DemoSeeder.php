<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Warehouses
        $main = Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            ['name' => 'Main Store — Via Roma', 'address' => 'Via Roma 42, Milano', 'is_default' => true]
        );
        $north = Warehouse::firstOrCreate(
            ['code' => 'WH-NORTH'],
            ['name' => 'North Depot', 'address' => 'Via delle Industrie 10, Monza']
        );
        $south = Warehouse::firstOrCreate(
            ['code' => 'WH-SOUTH'],
            ['name' => 'South Depot', 'address' => 'Via Napoli 8, Torino']
        );
        $warehouses = [$main, $north, $south];

        $categories = Category::all();
        if ($categories->isEmpty()) {
            $categories = Category::factory(8)->create();
        }

        // Products: ~48 realistic SKUs
        $products = Product::factory(48)->create([
            'category_id' => fn () => $categories->random()->id,
        ]);

        // Inventories: every product in main, subset in other warehouses
        $admin = User::where('email', 'admin@stockly.test')->first();

        foreach ($products as $product) {
            foreach ($warehouses as $i => $wh) {
                if ($i > 0 && fake()->boolean(45)) {
                    continue; // not stocked everywhere
                }
                $qty = $i === 0
                    ? fake()->numberBetween(5, 150)
                    : fake()->numberBetween(0, 60);

                // Force a few low-stock cases (~15%)
                $threshold = fake()->numberBetween(5, 15);
                if (fake()->boolean(15)) {
                    $qty = fake()->numberBetween(0, $threshold);
                }

                $inv = Inventory::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $wh->id],
                    ['quantity' => $qty, 'low_stock_threshold' => $threshold]
                );

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $wh->id,
                    'type' => 'in',
                    'quantity' => $qty,
                    'balance_after' => $qty,
                    'reason' => 'Initial stocktake',
                    'user_id' => $admin?->id,
                ]);
            }
        }

        // Customers
        $customers = Customer::factory(35)->create();

        // Orders over last 90 days with items + stock consumption + payments
        $users = User::all();
        $taxRate = 0.22;

        for ($k = 0; $k < 90; $k++) {
            $customer = $customers->random();
            $warehouse = $warehouses[0]; // sell from main store
            if (fake()->boolean(20)) {
                $warehouse = fake()->randomElement($warehouses);
            }

            $itemCount = fake()->numberBetween(1, 5);
            $chosen = $products->random($itemCount);
            if (! ($chosen instanceof \Illuminate\Support\Collection)) {
                $chosen = collect([$chosen]);
            }

            $subtotal = 0;
            $lines = [];
            foreach ($chosen as $product) {
                $inv = Inventory::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouse->id)->first();
                if (! $inv || $inv->quantity < 1) {
                    continue;
                }
                $max = min($inv->quantity, 8);
                $qty = fake()->numberBetween(1, max(1, $max));
                $lineTotal = round($qty * (float) $product->price, 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_price' => (float) $product->price,
                    'line_total' => $lineTotal,
                ];
            }

            if (empty($lines) || $subtotal <= 0) {
                continue;
            }

            $discount = fake()->boolean(15) ? round($subtotal * 0.05, 2) : 0;
            $taxable = $subtotal - $discount;
            $tax = round($taxable * $taxRate, 2);
            $total = round($taxable + $tax, 2);

            $status = fake()->randomElement(['completed', 'completed', 'completed', 'paid', 'shipped', 'confirmed']);
            $createdAt = fake()->dateTimeBetween('-90 days', 'now');

            $order = Order::create([
                'order_number' => 'ORD-'.$createdAt->format('Ymd').'-'.strtoupper(Str::random(6)),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'user_id' => $users->random()->id,
                'status' => $status,
                'subtotal' => round($subtotal, 2),
                'tax_rate' => $taxRate,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total' => $total,
                'notes' => fake()->boolean(15) ? fake()->sentence() : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($lines as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['line_total'],
                ]);

                // Decrement stock + movement (no events to keep seeder fast/deterministic)
                $inv = Inventory::where('product_id', $line['product']->id)
                    ->where('warehouse_id', $warehouse->id)->first();
                if ($inv) {
                    $inv->quantity = max(0, $inv->quantity - $line['qty']);
                    $inv->save();
                    InventoryMovement::create([
                        'product_id' => $line['product']->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'out',
                        'quantity' => -$line['qty'],
                        'balance_after' => $inv->quantity,
                        'reason' => 'Sale '.$order->order_number,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'user_id' => $order->user_id,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }

            // Payments: paid/completed/shipped orders get 1 payment; sometimes partial
            if (in_array($status, ['paid', 'shipped', 'completed'])) {
                $paidAmount = $total;
                if (fake()->boolean(10)) {
                    $paidAmount = round($total * 0.6, 2); // partial
                }
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $paidAmount,
                    'method' => fake()->randomElement(['cash', 'card', 'card', 'bank_transfer']),
                    'reference' => fake()->boolean(50) ? fake()->bothify('TRX-####-???') : null,
                    'paid_at' => $createdAt,
                    'user_id' => $order->user_id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $customer->increment('total_spent', $total);
        }
    }
}
