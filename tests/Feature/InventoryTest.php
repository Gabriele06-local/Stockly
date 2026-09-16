<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjust_creates_inventory_and_movement(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $inv = app(InventoryService::class)->adjust($product->id, $warehouse->id, 20, $user->id, 'Goods receipt');

        $this->assertEquals(20, $inv->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => 'in',
            'quantity' => 20,
            'balance_after' => 20,
        ]);
    }

    public function test_adjust_negative_beyond_zero_fails(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        app(InventoryService::class)->adjust($product->id, $warehouse->id, 3);

        $this->expectException(ValidationException::class);
        app(InventoryService::class)->adjust($product->id, $warehouse->id, -10);
    }

    public function test_transfer_moves_stock_atomically(): void
    {
        $product = Product::factory()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $svc = app(InventoryService::class);

        $svc->adjust($product->id, $from->id, 30);
        $svc->transfer($product->id, $from->id, $to->id, 12);

        $this->assertEquals(18, Inventory::where('product_id', $product->id)->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertEquals(12, Inventory::where('product_id', $product->id)->where('warehouse_id', $to->id)->value('quantity'));
    }

    public function test_transfer_with_insufficient_stock_rolls_back(): void
    {
        $product = Product::factory()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $svc = app(InventoryService::class);

        $svc->adjust($product->id, $from->id, 5);

        try {
            $svc->transfer($product->id, $from->id, $to->id, 99);
            $this->fail('Expected ValidationException');
        } catch (ValidationException) {
            // expected
        }

        $this->assertEquals(5, Inventory::where('product_id', $product->id)->where('warehouse_id', $from->id)->value('quantity'));
        $this->assertNull(Inventory::where('product_id', $product->id)->where('warehouse_id', $to->id)->first());
    }

    public function test_low_stock_scope(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 2, 'low_stock_threshold' => 5]);
        $p2 = Product::factory()->create();
        Inventory::create(['product_id' => $p2->id, 'warehouse_id' => $warehouse->id, 'quantity' => 50, 'low_stock_threshold' => 5]);

        $this->assertEquals(1, Inventory::lowStock()->count());
        $this->assertTrue(Inventory::lowStock()->first()->isLowStock());
    }

    public function test_web_inventory_requires_auth(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
    }
}
