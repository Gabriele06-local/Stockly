<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['in', 'out', 'adjustment']);
        $qty = fake()->numberBetween(1, 50);

        return [
            'type' => $type,
            'quantity' => $type === 'out' ? -$qty : $qty,
            'balance_after' => fake()->numberBetween(0, 200),
            'reason' => fake()->boolean(50) ? fake()->sentence() : null,
        ];
    }
}
