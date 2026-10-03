<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const CATEGORIES = [
        'Electronics',
        'Home & Kitchen',
        'Footwear',
        'Fashion',
        'Accessories',
        'Sports & Outdoor',
    ];

    /**
     * Sub-categories: parent name => child names.
     *
     * @var array<string, list<string>>
     */
    public const SUBCATEGORIES = [
        'Electronics' => ['Smartphones', 'Laptops & Tablets', 'Wearables & Audio', 'TVs'],
        'Home & Kitchen' => ['Kitchen Appliances', 'Home Care'],
        'Footwear' => ['Sneakers', 'Running Shoes'],
        'Fashion' => ['T-Shirts', 'Jackets & Hoodies'],
        'Accessories' => ['Bags', 'Chargers'],
        'Sports & Outdoor' => ['Fitness', 'Football'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            $slug = Str::slug($name);

            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'image' => "/images/categories/{$slug}.jpg",
                ],
            );
        }

        foreach (self::SUBCATEGORIES as $parentName => $children) {
            $parent = Category::where('slug', Str::slug($parentName))->first();

            foreach ($children as $name) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'parent_id' => $parent?->id],
                );
            }
        }
    }
}
