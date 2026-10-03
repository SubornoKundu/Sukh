<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductVariant;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seed_runs_twice_and_every_storefront_and_admin_page_renders(): void
    {
        $this->seed();
        $this->seed(); // re-running must not duplicate or crash

        $this->assertGreaterThan(0, Category::whereNotNull('parent_id')->count());
        $this->assertGreaterThan(0, ProductVariant::count());
        $this->assertGreaterThan(0, Order::where('status', 'shipped')->count());
        $this->assertGreaterThan(0, ProductQuestion::count());
        $this->assertGreaterThan(0, Slider::count());
        $this->assertSame(Product::count(), Product::distinct('slug')->count('slug'));

        foreach (['/', '/products', '/products?sort=popular', '/products?min_price=100&max_price=900000', '/search/suggest?q=a'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/products/'.Product::has('variants')->first()->slug)->assertOk();
        $this->get('/products/'.Product::doesntHave('variants')->first()->slug)->assertOk();

        $admin = User::where('role', 'admin')->firstOrFail();
        foreach (['/admin', '/admin?status=pending', '/admin/orders', '/admin/products', '/admin/categories', '/admin/inventory', '/admin/questions', '/admin/coupons', '/admin/sliders'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $order = Order::first();
        $this->actingAs($admin)->get('/admin/orders/'.$order->id)->assertOk();
        $this->actingAs($admin)->get('/admin/orders/print?ids='.$order->id)->assertOk();

        $customer = User::where('role', 'customer')->has('orders')->first() ?? User::where('role', 'customer')->firstOrFail();
        foreach (['/orders', '/account/addresses', '/cart', '/wishlist'] as $url) {
            $this->actingAs($customer)->get($url)->assertOk();
        }
    }
}
