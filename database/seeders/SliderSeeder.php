<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * [slug, title, subtitle, link, sort_order]
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int}>
     */
    public const SLIDERS = [
        ['big-season-sale', 'Big Season Sale', 'Up to 40% off electronics & fashion', '/products', 1],
        ['new-arrivals', 'New Arrivals Are In', 'Fresh gadgets and gear, just landed', '/products?sort=latest', 2],
        ['free-shipping', 'Free Shipping This Week', 'On every order over ৳5,000', '/products', 3],
    ];

    public function run(): void
    {
        foreach (self::SLIDERS as [$slug, $title, $subtitle, $link, $sortOrder]) {
            Slider::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle' => $subtitle,
                    'image' => "/images/sliders/{$slug}.jpg",
                    'link' => $link,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );
        }
    }
}
