<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Beverages', 'Water, juices, coffee, soft drinks'],
            ['Snacks & Sweets', 'Chips, chocolate, biscuits, candy'],
            ['Dairy & Eggs', 'Milk, cheese, yogurt, eggs'],
            ['Fresh Produce', 'Fruit and vegetables'],
            ['Frozen Foods', 'Frozen pizza, vegetables, ice cream'],
            ['Bakery', 'Bread, croissants, cakes'],
            ['Household', 'Cleaning, paper, home care'],
            ['Personal Care', 'Shampoo, soap, hygiene'],
            ['Stationery', 'Notebooks, pens, office supplies'],
            ['Pet Supplies', 'Food and care for pets'],
        ];

        foreach ($categories as [$name, $desc]) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $desc]
            );
        }
    }
}
