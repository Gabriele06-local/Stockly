<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 800);

        return [
            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->bothify('######')),
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'user_id' => User::factory(),
            'status' => fake()->randomElement(['confirmed', 'paid', 'shipped', 'completed']),
            'subtotal' => $subtotal,
            'tax_rate' => 0.22,
            'tax_amount' => round($subtotal * 0.22, 2),
            'discount_amount' => 0,
            'total' => round($subtotal * 1.22, 2),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
