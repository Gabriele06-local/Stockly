<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setupData(): array
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = Warehouse::factory()->create(['is_default' => true]);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10.00, 'cost' => 6.00, 'is_active' => true]);

        app(InventoryService::class)->adjust(
            $product->id, $warehouse->id, 50, $user->id, 'Test setup'
        );

        return compact('user', 'warehouse', 'customer', 'product');
    }

    public function test_order_creation_decrements_stock_in_transaction(): void
    {
        ['user' => $user, 'warehouse' => $wh, 'customer' => $customer, 'product' => $product] = $this->setupData();

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $wh->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'tax_rate' => 0.22,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        // 5 × 10.00 = 50 subtotal, tax 11.00, total 61.00
        $this->assertEquals(50.00, (float) $order->subtotal);
        $this->assertEquals(61.00, (float) $order->total);
        $this->assertEquals(45, Inventory::where('product_id', $product->id)->where('warehouse_id', $wh->id)->value('quantity'));

        // Movement logged
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $wh->id,
            'type' => 'out',
            'quantity' => -5,
        ]);

        // Customer lifetime value updated
        $this->assertEquals(61.00, (float) $customer->fresh()->total_spent);
    }

    public function test_draft_order_does_not_consume_stock(): void
    {
        ['user' => $user, 'warehouse' => $wh, 'customer' => $customer, 'product' => $product] = $this->setupData();

        app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $wh->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $this->assertEquals(50, Inventory::where('product_id', $product->id)->where('warehouse_id', $wh->id)->value('quantity'));
    }

    public function test_insufficient_stock_fails_and_rolls_back(): void
    {
        ['user' => $user, 'warehouse' => $wh, 'customer' => $customer, 'product' => $product] = $this->setupData();

        try {
            app(OrderService::class)->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $wh->id,
                'user_id' => $user->id,
                'status' => 'confirmed',
                'items' => [['product_id' => $product->id, 'quantity' => 999]],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }

        // No partial order persisted, stock untouched
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertEquals(50, Inventory::where('product_id', $product->id)->where('warehouse_id', $wh->id)->value('quantity'));
    }

    public function test_cancel_restores_stock(): void
    {
        ['user' => $user, 'warehouse' => $wh, 'customer' => $customer, 'product' => $product] = $this->setupData();

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $wh->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ]);

        $this->assertEquals(40, Inventory::where('product_id', $product->id)->where('warehouse_id', $wh->id)->value('quantity'));

        app(OrderService::class)->cancel($order, $user->id);

        $this->assertEquals(50, Inventory::where('product_id', $product->id)->where('warehouse_id', $wh->id)->value('quantity'));
        $this->assertEquals('cancelled', $order->fresh()->status->value);
    }

    public function test_web_order_validation_errors(): void
    {
        ['user' => $user] = $this->setupData();

        $response = $this->actingAs($user)->post('/orders', [
            'customer_id' => 9999,
            'warehouse_id' => 9999,
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['customer_id', 'warehouse_id', 'items']);
    }

    public function test_payment_cannot_exceed_balance(): void
    {
        ['user' => $user, 'warehouse' => $wh, 'customer' => $customer, 'product' => $product] = $this->setupData();

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $wh->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'items' => [['product_id' => $product->id, 'quantity' => 2]], // total 24.40
        ]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/payments", [
            'amount' => 9999,
            'method' => 'cash',
        ]);

        $response->assertSessionHas('error');
    }
}
