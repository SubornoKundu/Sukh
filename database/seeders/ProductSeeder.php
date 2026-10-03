<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * [slug, name, category slug, brand slug, price, discount price|null, stock, featured, description]
     *
     * The slug is listed explicitly (rather than derived from the name
     * with Str::slug()) so it stays pinned to the matching file under
     * public/images/products/ -- several names below include the brand
     * ("Samsung Galaxy S24 Ultra"), which would otherwise slug to
     * "samsung-galaxy-s24-ultra" and no longer match "galaxy-s24-ultra.jpg".
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: float, 5: ?float, 6: int, 7: bool, 8: string}>
     */
    public const PRODUCTS = [
        ['iphone-15-pro-max', 'iPhone 15 Pro Max', 'electronics', 'apple', 149999, 139999, 25, true, 'Apple\'s flagship phone with a titanium frame, A17 Pro chip, and a pro-grade camera system.'],
        ['macbook-air-m3', 'MacBook Air M3', 'electronics', 'apple', 139999, null, 15, true, 'Thin, fanless, and fast -- the M3 MacBook Air handles everyday work and creative apps with ease.'],
        ['apple-watch-series-10', 'Apple Watch Series 10', 'electronics', 'apple', 42999, null, 30, false, 'A slimmer, brighter Apple Watch with advanced health and fitness tracking.'],
        ['airpods-pro-2', 'AirPods Pro 2', 'electronics', 'apple', 24999, 21999, 40, true, 'Active noise cancellation, adaptive audio, and all-day battery life in a compact case.'],
        ['magsafe-charger', 'MagSafe Charger', 'accessories', 'apple', 5499, null, 60, false, 'Snap-on wireless charging for iPhone, up to 15W.'],
        ['galaxy-s24-ultra', 'Samsung Galaxy S24 Ultra', 'electronics', 'samsung', 134999, 124999, 20, true, 'A 200MP camera, S Pen, and a bright 6.8" display built for productivity.'],
        ['galaxy-tab-s9', 'Samsung Galaxy Tab S9', 'electronics', 'samsung', 79999, null, 18, false, 'A flagship Android tablet with an AMOLED display and included S Pen.'],
        ['qled-4k-tv-55', 'Samsung 55" QLED 4K TV', 'electronics', 'samsung', 89999, null, 10, false, 'Quantum Dot color, 4K upscaling, and a slim design for your living room.'],
        ['inverter-microwave-oven', 'Samsung Inverter Microwave Oven', 'home-kitchen', 'samsung', 18999, null, 22, false, 'Even, efficient heating with inverter technology and easy-clean interior.'],
        ['redmi-note-13-pro', 'Xiaomi Redmi Note 13 Pro', 'electronics', 'xiaomi', 27999, null, 35, true, 'A 200MP camera and 120Hz AMOLED display at a genuinely affordable price.'],
        ['mi-band-8', 'Xiaomi Mi Band 8', 'electronics', 'xiaomi', 3499, null, 80, false, 'Lightweight fitness tracking with heart-rate, SpO2, and sleep monitoring.'],
        ['robot-vacuum-x10-plus', 'Xiaomi Robot Vacuum X10+', 'home-kitchen', 'xiaomi', 32999, 28999, 12, false, 'Self-emptying robot vacuum and mop with smart room mapping.'],
        ['smart-air-purifier-4', 'Xiaomi Smart Air Purifier 4', 'home-kitchen', 'xiaomi', 12999, null, 25, false, 'HEPA filtration with real-time air quality display and app control.'],
        ['air-max-270', 'Nike Air Max 270', 'footwear', 'nike', 12999, null, 28, true, 'A large Max Air unit underfoot for all-day comfort and street-ready style.'],
        ['revolution-6-running-shoes', 'Nike Revolution 6 Running Shoes', 'footwear', 'nike', 6499, null, 45, false, 'Lightweight cushioning built for everyday runs and casual wear.'],
        ['dri-fit-running-tee', 'Nike Dri-FIT Running Tee', 'fashion', 'nike', 2499, null, 60, false, 'Sweat-wicking fabric that keeps you dry and comfortable on the run.'],
        ['tech-fleece-hoodie', 'Nike Tech Fleece Hoodie', 'fashion', 'nike', 7999, 6999, 20, false, 'A soft, lightweight fleece hoodie that keeps its shape wash after wash.'],
        ['heritage-backpack', 'Nike Heritage Backpack', 'accessories', 'nike', 3999, null, 33, false, 'A durable everyday backpack with a padded laptop sleeve.'],
        ['football-pro-match-ball', 'Nike Football Pro Match Ball', 'sports-outdoor', 'nike', 3499, null, 40, false, 'A match-quality ball with a textured surface for consistent flight.'],
        ['ultraboost-22', 'Adidas Ultraboost 22', 'footwear', 'adidas', 15999, 13999, 18, true, 'Responsive Boost cushioning wrapped in a Primeknit upper.'],
        ['samba-og', 'Adidas Samba OG', 'footwear', 'adidas', 9999, null, 38, false, 'The classic low-top leather trainer that never really left.'],
        ['originals-trefoil-tee', 'Adidas Originals Trefoil Tee', 'fashion', 'adidas', 2299, null, 55, false, 'A soft cotton tee with the iconic Trefoil print.'],
        ['tiro-23-track-jacket', 'Adidas Tiro 23 Track Jacket', 'fashion', 'adidas', 5499, null, 26, false, 'A classic three-stripe track jacket built for training and travel.'],
        ['duffel-gym-bag', 'Adidas Duffel Gym Bag', 'accessories', 'adidas', 4499, null, 30, false, 'A spacious, durable duffel with a separate shoe compartment.'],
        ['training-yoga-mat', 'Adidas Training Yoga Mat', 'sports-outdoor', 'adidas', 2999, null, 50, true, 'A non-slip, cushioned mat for yoga, stretching, and floor workouts.'],
    ];

    /**
     * Which sub-category (slug) each product is filed under.
     *
     * @var array<string, string>
     */
    public const SUBCATEGORY = [
        'iphone-15-pro-max' => 'smartphones',
        'galaxy-s24-ultra' => 'smartphones',
        'redmi-note-13-pro' => 'smartphones',
        'macbook-air-m3' => 'laptops-tablets',
        'galaxy-tab-s9' => 'laptops-tablets',
        'apple-watch-series-10' => 'wearables-audio',
        'airpods-pro-2' => 'wearables-audio',
        'mi-band-8' => 'wearables-audio',
        'qled-4k-tv-55' => 'tvs',
        'inverter-microwave-oven' => 'kitchen-appliances',
        'robot-vacuum-x10-plus' => 'home-care',
        'smart-air-purifier-4' => 'home-care',
        'air-max-270' => 'sneakers',
        'samba-og' => 'sneakers',
        'revolution-6-running-shoes' => 'running-shoes',
        'ultraboost-22' => 'running-shoes',
        'dri-fit-running-tee' => 't-shirts',
        'originals-trefoil-tee' => 't-shirts',
        'tech-fleece-hoodie' => 'jackets-hoodies',
        'tiro-23-track-jacket' => 'jackets-hoodies',
        'heritage-backpack' => 'bags',
        'duffel-gym-bag' => 'bags',
        'magsafe-charger' => 'chargers',
        'training-yoga-mat' => 'fitness',
        'football-pro-match-ball' => 'football',
    ];

    /**
     * Size / colour / type options. Row = [size, color, type, extra price, stock].
     * A product with options takes its stock from these rows.
     *
     * @var array<string, list<array{0: ?string, 1: ?string, 2: ?string, 3: int, 4: int}>>
     */
    public const VARIANTS = [
        'iphone-15-pro-max' => [
            [null, 'Natural Titanium', '256GB', 0, 9],
            [null, 'Blue Titanium', '256GB', 0, 3],
            [null, 'Natural Titanium', '512GB', 20000, 8],
            [null, 'Black Titanium', '512GB', 20000, 0],
        ],
        'galaxy-s24-ultra' => [
            [null, 'Titanium Gray', '256GB', 0, 12],
            [null, 'Titanium Black', '512GB', 15000, 8],
        ],
        'air-max-270' => [
            ['40', 'Black', null, 0, 6], ['41', 'Black', null, 0, 8], ['42', 'Black', null, 0, 2],
            ['43', 'Black', null, 0, 7], ['44', 'Black', null, 0, 5],
        ],
        'ultraboost-22' => [
            ['40', 'White', null, 0, 4], ['41', 'White', null, 0, 6], ['42', 'White', null, 0, 5], ['43', 'White', null, 0, 3],
        ],
        'samba-og' => [
            ['40', 'Cream', null, 0, 10], ['41', 'Cream', null, 0, 12], ['42', 'Cream', null, 0, 9], ['43', 'Cream', null, 0, 7],
        ],
        'dri-fit-running-tee' => [
            ['S', 'Black', null, 0, 14], ['M', 'Black', null, 0, 18], ['L', 'Black', null, 0, 16], ['XL', 'Black', null, 0, 12],
        ],
        'originals-trefoil-tee' => [
            ['M', 'White', null, 0, 20], ['L', 'White', null, 0, 20], ['M', 'Navy', null, 0, 8], ['L', 'Navy', null, 0, 7],
        ],
        'tech-fleece-hoodie' => [
            ['M', 'Grey', null, 0, 6], ['L', 'Grey', null, 0, 8], ['XL', 'Grey', null, 0, 6],
        ],
    ];

    /**
     * Spec table rows for the "Specifications" tab: [label, value].
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const SPECS = [
        'iphone-15-pro-max' => [['Display', '6.7" Super Retina XDR OLED'], ['Chip', 'A17 Pro'], ['Camera', '48MP main + 12MP ultra wide + 12MP 5x telephoto'], ['Battery', 'Up to 29 hours video playback'], ['Connector', 'USB-C'], ['Warranty', '1 year brand warranty']],
        'galaxy-s24-ultra' => [['Display', '6.8" Dynamic AMOLED 2X, 120Hz'], ['Processor', 'Snapdragon 8 Gen 3'], ['Camera', '200MP main + 50MP 5x + 10MP 3x + 12MP ultra wide'], ['Battery', '5000 mAh, 45W charging'], ['Warranty', '1 year brand warranty']],
        'macbook-air-m3' => [['Display', '13.6" Liquid Retina'], ['Chip', 'Apple M3, 8-core CPU'], ['Memory', '8GB unified'], ['Storage', '256GB SSD'], ['Weight', '1.24 kg'], ['Warranty', '1 year brand warranty']],
        'airpods-pro-2' => [['Noise control', 'Active Noise Cancellation, Transparency'], ['Chip', 'H2'], ['Battery', 'Up to 6 hours (30 with case)'], ['Charging', 'MagSafe / USB-C / Lightning'], ['Water resistance', 'IP54']],
        'air-max-270' => [['Upper', 'Breathable mesh'], ['Cushioning', 'Max Air 270 unit'], ['Sole', 'Rubber outsole'], ['Origin', 'Vietnam'], ['Care', 'Wipe clean with a damp cloth']],
        'ultraboost-22' => [['Upper', 'Primeknit'], ['Cushioning', 'Boost midsole'], ['Outsole', 'Continental rubber'], ['Drop', '10 mm'], ['Care', 'Hand wash only']],
        'dri-fit-running-tee' => [['Fabric', '100% polyester Dri-FIT'], ['Fit', 'Standard fit'], ['Care', 'Machine wash cold']],
        'tech-fleece-hoodie' => [['Fabric', '80% cotton, 20% polyester'], ['Fit', 'Slim fit'], ['Features', 'Kangaroo pocket, drawcord hood'], ['Care', 'Machine wash cold']],
        'robot-vacuum-x10-plus' => [['Suction', '4000 Pa'], ['Battery', '5200 mAh, up to 3 hours'], ['Features', 'Self-emptying dock, mopping, LDS mapping'], ['Control', 'Mi Home app, voice assistants']],
        'qled-4k-tv-55' => [['Screen', '55" QLED 4K UHD'], ['Refresh rate', '120Hz'], ['Smart TV', 'Tizen OS'], ['Ports', '3x HDMI, 2x USB'], ['Warranty', '2 years']],
    ];

    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $brands = Brand::pluck('id', 'slug');

        foreach (self::PRODUCTS as [$slug, $name, $categorySlug, $brandSlug, $price, $discountPrice, $stock, $featured, $description]) {
            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categories[$categorySlug],
                    'brand_id' => $brands[$brandSlug],
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'discount_price' => $discountPrice,
                    'image' => "/images/products/{$slug}.jpg",
                    'stock' => $stock,
                    'is_featured' => $featured,
                    'is_active' => true,
                ],
            );
        }

        $this->extras();
    }

    /**
     * Sub-categories, size/colour options and specifications.
     */
    protected function extras(): void
    {
        $sub = Category::whereNotNull('parent_id')->pluck('id', 'slug');

        foreach (self::SUBCATEGORY as $productSlug => $categorySlug) {
            if (isset($sub[$categorySlug])) {
                Product::where('slug', $productSlug)->update(['category_id' => $sub[$categorySlug]]);
            }
        }

        foreach (self::VARIANTS as $productSlug => $rows) {
            $product = Product::where('slug', $productSlug)->first();

            if (! $product) {
                continue;
            }

            $product->variants()->delete();

            foreach ($rows as $i => [$size, $color, $type, $extra, $stock]) {
                $product->variants()->create([
                    'size' => $size,
                    'color' => $color,
                    'type' => $type,
                    'extra_price' => $extra,
                    'stock' => $stock,
                    'sort_order' => $i,
                ]);
            }

            $product->syncStockFromVariants();
        }

        foreach (self::SPECS as $productSlug => $rows) {
            Product::where('slug', $productSlug)->first()?->update([
                'specifications' => array_map(fn ($r) => ['label' => $r[0], 'value' => $r[1]], $rows),
            ]);
        }
    }
}
