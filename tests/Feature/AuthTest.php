<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
    }

    public function test_admin_can_login_via_web(): void
    {
        $user = User::factory()->create(['email' => 'admin@test.test', 'role' => 'admin']);

        $response = $this->post('/login', [
            'email' => 'admin@test.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_rejected(): void
    {
        User::factory()->create(['email' => 'a@a.it']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'a@a.it',
            'password' => 'wrong',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_cannot_access_dashboard(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_api_login_issues_token(): void
    {
        $user = User::factory()->create(['email' => 'api@test.test', 'role' => 'staff']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'api@test.test',
            'password' => 'password',
            'device_name' => 'tests',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_staff_cannot_delete_product_but_admin_can(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $this->actingAs($staff)->delete("/products/{$product->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/products/{$product->id}")->assertRedirect();
    }
}
