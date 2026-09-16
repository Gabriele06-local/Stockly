<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $base = fake()->randomElement([
            'Beverages', 'Snacks', 'Dairy & Eggs', 'Fresh Produce',
            'Frozen Foods', 'Bakery', 'Household', 'Personal Care',
            'Electronics Accessories', 'Stationery', 'Pet Supplies', 'Baby Care',
        ]);
        $suffix = fake()->boolean(30) ? ' '.fake()->word() : '';
        $name = trim($base.$suffix);

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->randomNumber(4)),
            'description' => fake()->boolean(70) ? fake()->sentence() : null,
        ];
    }
}
