<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $isCompany = fake()->boolean(35);

        return [
            'name' => fake()->name(),
            'email' => fake()->boolean(85) ? fake()->unique()->safeEmail() : null,
            'phone' => fake()->boolean(80) ? fake()->phoneNumber() : null,
            'company' => $isCompany ? fake()->company() : null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'notes' => fake()->boolean(25) ? fake()->sentence() : null,
            'total_spent' => 0,
        ];
    }
}
