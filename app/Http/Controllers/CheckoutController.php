<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Services\CheckoutException;
use App\Services\CouponService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected CouponService $coupons,
    ) {}

    /**
     * Checkout page: order summary, coupon, shipping details and payment
     * method. Bounces back to the cart if there's nothing to check out.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $cartItems = $user->cartItems()->with('product')->get();
        $addresses = $user->addresses()->get();
        $default = $addresses->firstWhere('is_default', true) ?? $addresses->first();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $coupon = null;
        $code = $request->session()->get('checkout.coupon');

        if ($code) {
            try {
                $coupon = $this->coupons->validate($code, round((float) $cartItems->sum('line_total'), 2), $user);
            } catch (CheckoutException $e) {
                // The coupon stopped being valid (cart changed, expired...).
                $request->session()->forget('checkout.coupon');
                session()->flash('error', $e->getMessage());
            }
        }

        return Inertia::render('Checkout/Index', [
            'cartItems' => $cartItems,
            'totals' => $this->orders->totals($cartItems, $coupon),
            'coupon' => $coupon ? ['code' => $coupon->code, 'label' => $coupon->value_label] : null,
            'addresses' => $addresses,
            'defaults' => [
                'shipping_name' => $default?->name ?? $user->name,
                'shipping_phone' => $default?->phone ?? $user->phone,
                'shipping_address' => $default?->address ?? $user->address,
                'shipping_city' => $default?->city ?? $user->city,
            ],
            'manualPayments' => [
                'bkash' => config('shop.payments.bkash'),
                'nagad' => config('shop.payments.nagad'),
            ],
        ]);
    }

    /**
     * Place the order. Cash on delivery is confirmed straight away; online
     * payment hands off to the (demo) SSLCommerz step.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'shipping_city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cod,online,bkash,nagad'],
            // bKash / Nagad (manual): the id from the payment confirmation SMS.
            'transaction_id' => ['nullable', 'required_if:payment_method,bkash,nagad', 'string', 'min:6', 'max:40'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        $method = match ($data['payment_method']) {
            'cod' => Order::METHOD_COD,
            'bkash' => Order::METHOD_BKASH,
            'nagad' => Order::METHOD_NAGAD,
            default => Order::METHOD_ONLINE,
        };

        try {
            $order = $this->orders->place(
                $request->user(),
                $data,
                $method,
                $request->session()->get('checkout.coupon'),
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        $request->session()->forget('checkout.coupon');

        if ($request->boolean('save_address')) {
            $this->rememberAddress($request->user(), $data);
        }

        if ($order->isOffline()) {
            return redirect()->route('orders.success', $order);
        }

        return redirect()->route('payment.show', $order);
    }

    /**
     * Save the shipping details to the customer's address book (once).
     *
     * @param  array<string, mixed>  $data
     */
    protected function rememberAddress(\App\Models\User $user, array $data): void
    {
        $exists = $user->addresses()
            ->where('address', $data['shipping_address'])
            ->where('phone', $data['shipping_phone'])
            ->exists();

        if ($exists || $user->addresses()->count() >= 10) {
            return;
        }

        Address::create([
            'user_id' => $user->id,
            'label' => $user->addresses()->exists() ? 'Saved' : 'Home',
            'name' => $data['shipping_name'],
            'phone' => $data['shipping_phone'],
            'address' => $data['shipping_address'],
            'city' => $data['shipping_city'] ?? null,
            'is_default' => ! $user->addresses()->exists(),
        ]);
    }
}
