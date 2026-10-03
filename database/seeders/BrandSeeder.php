<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const BRANDS = ['Apple', 'Samsung', 'Xiaomi', 'Nike', 'Adidas'];

    public function run(): void
    {
        foreach (self::BRANDS as $name) {
            $slug = Str::slug($name);

            Brand::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'logo' => "/images/brands/{$slug}.png",
                ],
            );
        }
    }
}
