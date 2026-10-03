<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default, on every Inertia
     * response. Keeping these here (rather than passing them from each
     * controller) is what lets the header show the cart/wishlist counts
     * and flash toasts on every page without repeating that logic.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'cartCount' => $user
                ? fn () => $user->cartItems()->sum('quantity')
                : 0,
            'wishlistCount' => $user
                ? fn () => $user->wishlists()->whereHas('product', fn ($q) => $q->active())->count()
                : 0,
            // Slide-out cart: refreshed on every Inertia response so the badge and
            // the drawer always match what is really in the cart.
            'cartDrawer' => $user
                ? fn () => $this->cartDrawer($user)
                : ['items' => [], 'subtotal' => 0],
            // Two-level category menu for the header (cached, see Category model).
            'navCategories' => fn () => Cache::remember(
                Category::NAV_CACHE_KEY,
                300,
                fn () => Category::whereNull('parent_id')
                    ->with('children:id,parent_id,name,slug')
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug'])
                    ->toArray(),
            ),
            'adminAlerts' => $user?->isAdmin()
                ? fn () => $this->adminAlerts()
                : null,
            'site' => [
                'name' => (string) config('site.name', config('app.name')),
                'tagline' => (string) config('site.tagline', ''),
                'logo' => config('site.logo'),
                'favicon' => config('site.favicon'),
                'primaryColor' => (string) config('site.primary_color', '#16a34a'),
                'announcement' => config('site.announcement_text')
                    ? ['text' => config('site.announcement_text'), 'link' => config('site.announcement_link')]
                    : null,
                'footerText' => config('site.footer_text'),
            ],
            'shop' => [
                'freeShippingOver' => (float) config('shop.free_shipping_over'),
                'shippingFee' => (float) config('shop.shipping_fee'),
                'whatsapp' => (string) config('shop.whatsapp'),
                'contact' => config('shop.contact'),
                'social' => config('shop.social'),
                'demoLogin' => (bool) config('shop.demo_login'),
                'settingsAccess' => (bool) config('shop.settings_access'),
                'lowStockThreshold' => (int) config('shop.low_stock_threshold'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, subtotal: float}
     */
    protected function cartDrawer(User $user): array
    {
        $items = $user->cartItems()->with('product:id,name,slug,image,price,discount_price,stock')->latest()->get();

        return [
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'image' => $item->variant?->image ?: $item->product->image,
                'variant_label' => $item->variant_label,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'original_price' => $item->product->hasDiscount()
                    ? round((float) $item->product->price + (float) ($item->variant?->extra_price ?? 0), 2)
                    : null,
                'line_total' => $item->line_total,
                'stock' => $item->available_stock,
            ])->values()->all(),
            'subtotal' => round((float) $items->sum('line_total'), 2),
        ];
    }

    /**
     * Numbers for the admin sidebar badges.
     *
     * @return array{low_stock: int, pending_orders: int, open_questions: int}
     */
    protected function adminAlerts(): array
    {
        return [
            'low_stock' => Product::where('is_active', true)
                ->where('stock', '<=', (int) config('shop.low_stock_threshold'))->count(),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'open_questions' => ProductQuestion::whereNull('answered_at')->count(),
        ];
    }
}
