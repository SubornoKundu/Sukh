<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Services\StorefrontCache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The storefront home page: hero slider, brand list, category list,
     * latest products, and featured products.
     *
     * Everything on it is the same for every visitor, so it is cached for a
     * short time (see StorefrontCache) instead of being rebuilt on every hit.
     */
    public function index(): Response
    {
        return Inertia::render('Home', StorefrontCache::remember('home', fn () => [
            'sliders' => Slider::active()->orderBy('sort_order')->get(),
            'brands' => Brand::withCount('products')->orderBy('name')->get(),
            'categories' => Category::withCount('products')->whereNull('parent_id')->orderBy('name')->get(),
            'latestProducts' => Product::with(['brand', 'category'])
                ->active()
                ->latest()
                ->orderByDesc('id')
                ->take(8)
                ->get(),
            'popularProducts' => $this->popularProducts(),
            'featuredProducts' => Product::with(['brand', 'category'])
                ->active()
                ->featured()
                ->latest()
                ->orderByDesc('id')
                ->take(8)
                ->get(),
        ]));
    }

    /**
     * The 8 best sellers that are still on sale, best first.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    protected function popularProducts()
    {
        // A little more than 8 ids, in case some of the top sellers have been hidden since.
        $ids = array_slice(StorefrontCache::popularProductIds(), 0, 60);

        if ($ids === []) {
            return collect();
        }

        $rank = array_flip($ids);

        return Product::with(['brand', 'category'])
            ->active()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $p) => $rank[$p->id])
            ->take(8)
            ->values();
    }
}
