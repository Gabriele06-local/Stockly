<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $brands = ['Barilla', 'Mulino Bianco', 'Lavazza', 'Ferrero', 'Galbani', 'Parmalat', 'Stockly Basics', 'VerdeBio', 'CasaNova', 'FreshLine'];
        $items = [
            'Pasta Spaghetti 500g', 'Olive Oil Extra Virgin 1L', 'Espresso Ground Coffee 250g',
            'Whole Milk 1L', 'Mozzarella 125g', 'Canned Tomatoes 400g', 'Rice Arborio 1kg',
            'Dark Chocolate 100g', 'Paper Towels 4 rolls', 'Dish Soap 500ml',
            'Shampoo 300ml', 'Notebook A4 96pp', 'Dog Croquettes 3kg', 'Baby Diapers size 4',
            'Mineral Water 6x1.5L', 'Orange Juice 1L', 'Croissants x4', 'Frozen Pizza Margherita',
            'USB-C Cable 1m', 'AA Batteries 8-pack',
        ];

        $name = fake()->randomElement($brands).' '.fake()->randomElement($items);
        $price = fake()->randomFloat(2, 1, 120);
        $cost = round($price * fake()->randomFloat(2, 0.45, 0.75), 2);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->randomNumber(5)),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-???')),
            'description' => fake()->boolean(70) ? fake()->paragraph() : null,
            'price' => $price,
            'cost' => $cost,
            'is_active' => fake()->boolean(92),
        ];
    }
}
