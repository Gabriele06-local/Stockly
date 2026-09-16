<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 5, 500),
            'method' => fake()->randomElement(['cash', 'card', 'bank_transfer', 'other']),
            'reference' => fake()->boolean(50) ? fake()->bothify('PAY-####-???') : null,
            'paid_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
