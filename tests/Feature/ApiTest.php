<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeaders(?User $user = null): array
    {
        $user ??= User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('tests')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_unauthenticated_api_returns_401(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/customers')->assertUnauthorized();
        $this->getJson('/api/inventory')->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_products_crud_search_pagination(): void
    {
        $headers = $this->authHeaders();
        $cat = Category::factory()->create(['name' => 'Beverages']);
        Product::factory()->create(['name' => 'Lavazza Espresso 250g', 'sku' => 'SKU-TEST-001', 'category_id' => $cat->id]);
        Product::factory()->count(20)->create();

        // Pagination default 15
        $this->getJson('/api/products', $headers)->assertOk()->assertJsonStructure(['data', 'links', 'meta']);

        // Search
        $this->getJson('/api/products?search=Lavazza', $headers)
            ->assertOk()
            ->assertJsonFragment(['sku' => 'SKU-TEST-001']);

        // Store validation error → 422 with proper shape
        $this->postJson('/api/products', [], $headers)
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        // Store ok → 201
        $this->postJson('/api/products', [
            'name' => 'Test Product',
            'sku' => 'SKU-NEW-999',
            'price' => 9.99,
            'category_id' => $cat->id,
        ], $headers)->assertCreated()->assertJsonFragment(['sku' => 'SKU-NEW-999']);
    }

    public function test_customers_api(): void
    {
        $headers = $this->authHeaders();
        Customer::factory()->count(3)->create(['name' => 'Mario Rossi']);

        $this->getJson('/api/customers?search=Mario', $headers)->assertOk();
        $this->postJson('/api/customers', ['name' => ''], $headers)->assertStatus(422);
    }

    public function test_inventory_low_stock_endpoint(): void
    {
        $headers = $this->authHeaders();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 1, 'low_stock_threshold' => 5]);

        $this->getJson('/api/inventory/low-stock', $headers)
            ->assertOk()
            ->assertJsonFragment(['product_id' => $product->id]);
    }

    public function test_orders_api_full_cycle(): void
    {
        $user = User::factory()->create(['role' => 'staff']);
        $headers = $this->authHeaders($user);
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 20.00, 'is_active' => true]);
        app(InventoryService::class)->adjust($product->id, $warehouse->id, 100, $user->id);

        // Create
        $res = $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ], $headers)->assertCreated();

        $orderId = $res->json('data.id');
        $this->assertNotNull($orderId);

        // Stock decremented
        $this->assertEquals(97, Inventory::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'));

        // Show
        $this->getJson("/api/orders/{$orderId}", $headers)->assertOk()->assertJsonFragment(['id' => $orderId]);

        // Insufficient stock → 422, no partial write
        $this->postJson('/api/orders', [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items' => [['product_id' => $product->id, 'quantity' => 9999]],
        ], $headers)->assertStatus(422);

        // Cancel restores
        $this->postJson("/api/orders/{$orderId}/cancel", [], $headers)->assertOk();
        $this->assertEquals(100, Inventory::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity'));
    }

    public function test_staff_cannot_delete_product_via_api(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}", [], $this->authHeaders($staff))->assertForbidden();
    }
}
