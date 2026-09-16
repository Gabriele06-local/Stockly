<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_dashboard_renders_kpis(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Dashboard');
    }

    public function test_dashboard_shows_gross_profit_and_margin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $warehouse = \App\Models\Warehouse::factory()->create();
        $customer = \App\Models\Customer::factory()->create();
        $product = \App\Models\Product::factory()->create(['price' => 10.00, 'cost' => 6.00, 'is_active' => true]);
        app(\App\Services\InventoryService::class)->adjust($product->id, $warehouse->id, 50, $user->id);

        // 2 × €10 = €20 net revenue, COGS = 2 × €6 = €12 → profit €8, margin 40%
        app(\App\Services\OrderService::class)->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $profit = app(\App\Services\DashboardService::class)->profitMonth();
        $this->assertEquals(20.00, $profit['revenue']);
        $this->assertEquals(12.00, $profit['cogs']);
        $this->assertEquals(8.00, $profit['profit']);
        $this->assertEquals(40.0, $profit['margin']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Gross profit')
            ->assertSee('Margin');
    }

    public function test_crud_pages_render(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get('/products')->assertOk();
        $this->actingAs($user)->get('/customers')->assertOk();
        $this->actingAs($user)->get('/orders')->assertOk();
        $this->actingAs($user)->get('/inventory')->assertOk();
    }
}
