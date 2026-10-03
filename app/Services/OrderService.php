<?php

namespace App\Services;

use App\Mail\LowStockMail;
use App\Mail\OrderStatusMail;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Products whose stock dropped to/below the alert threshold while an
     * order was being confirmed; an e-mail goes out once the transaction ends.
     *
     * @var array<int, Product>
     */
    protected array $lowStock = [];

    public function __construct(
        protected CouponService $coupons,
        protected SslCommerzDemoGateway $gateway,
    ) {}

    /**
     * Price breakdown for a set of cart items and an optional coupon.
     *
     * @param  Collection<int, CartItem>  $cartItems
     * @return array{subtotal: float, discount: float, shipping: float, total: float}
     */
    public function totals(Collection $cartItems, ?Coupon $coupon = null): array
    {
        $subtotal = round((float) $cartItems->sum('line_total'), 2);
        $discount = $coupon ? $this->coupons->discountFor($coupon, $subtotal) : 0.0;
        $payable = max(0.0, round($subtotal - $discount, 2));

        $threshold = (float) config('shop.free_shipping_over');
        $shipping = $threshold > 0 && $payable >= $threshold ? 0.0 : (float) config('shop.shipping_fee');

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => round($payable + $shipping, 2),
        ];
    }

    /**
     * Turn the user's cart into an order.
     *
     * COD orders are confirmed immediately (stock deducted, cart emptied).
     * Online orders stay pending until payOnline() succeeds.
     *
     * @param  array<string, mixed>  $shipping
     *
     * @throws CheckoutException
     */
    public function place(User $user, array $shipping, string $method, ?string $couponCode = null): Order
    {
        $this->lowStock = [];

        $order = DB::transaction(function () use ($user, $shipping, $method, $couponCode) {
            // Serialise this customer's checkouts: a double tap / two tabs must not
            // create two orders from the same cart.
            User::whereKey($user->id)->lockForUpdate()->first();

            $cartItems = $user->cartItems()->with(['product', 'variant'])->get();

            if ($cartItems->isEmpty()) {
                throw new CheckoutException('Your cart is empty.');
            }

            $this->assertAvailable($cartItems);

            $subtotal = round((float) $cartItems->sum('line_total'), 2);
            $coupon = $couponCode ? $this->coupons->validate($couponCode, $subtotal, $user) : null;
            $totals = $this->totals($cartItems, $coupon);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $totals['subtotal'],
                'shipping_fee' => $totals['shipping'],
                'discount_amount' => $totals['discount'],
                'coupon_code' => $coupon?->code,
                'total_amount' => $totals['total'],
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_PENDING,
                'payment_method' => in_array($method, Order::OFFLINE_METHODS, true) ? $method : Order::METHOD_ONLINE,
                'transaction_id' => $this->manualReference($method, $shipping),
                'shipping_name' => $shipping['shipping_name'],
                'shipping_phone' => $shipping['shipping_phone'],
                'shipping_address' => $shipping['shipping_address'],
                'shipping_city' => $shipping['shipping_city'] ?? '',
                'notes' => $shipping['notes'] ?? null,
            ]);

            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id,
                    'variant_label' => $cartItem->variant?->label,
                    'product_name' => $cartItem->product->name,
                    'product_image' => $cartItem->variant?->image ?: $cartItem->product->image,
                    'price' => $cartItem->unit_price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $cartItem->line_total,
                ]);
            }

            if ($order->isOffline()) {
                $this->confirm($order);
            }

            return $order;
        });

        $this->notify($order, 'We received your order');
        $this->flushLowStockAlerts();

        return $order;
    }

    /**
     * Complete a (demo) online payment: confirm the order, take the stock and
     * mark it paid. Safe to call twice -- an already-paid order is returned
     * untouched.
     *
     * @throws CheckoutException
     */
    public function payOnline(Order $order, string $method): Order
    {
        $this->lowStock = [];

        $paid = DB::transaction(function () use ($order, $method) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isPaid()) {
                return null;
            }

            if (! $order->awaitingPayment()) {
                throw new CheckoutException('This order can no longer be paid.');
            }

            $this->confirm($order);
            $this->gateway->complete($order, $method);

            return $order;
        });

        if ($paid) {
            $this->notify($paid, 'Payment received');
            $this->flushLowStockAlerts();
        }

        return $paid ?? $order->refresh();
    }

    /**
     * Cancel an order, putting stock and coupon use back if they had been
     * committed, and flagging a paid order as refunded.
     *
     * @throws CheckoutException
     */
    public function cancel(Order $order): Order
    {
        $cancelled = DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array(Order::STATUS_CANCELLED, $order->next_statuses, true)) {
                throw new CheckoutException('This order can no longer be cancelled.');
            }

            if ($order->stock_deducted) {
                foreach ($order->items()->with(['product', 'variant'])->get() as $item) {
                    $item->product?->increment('stock', $item->quantity);
                    $item->variant?->increment('stock', $item->quantity);
                }

                if ($order->coupon_code) {
                    Coupon::where('code', $order->coupon_code)->where('used_count', '>', 0)->decrement('used_count');
                }
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'stock_deducted' => false,
                'cancelled_at' => now(),
                'payment_status' => $order->isPaid() ? Order::PAYMENT_REFUNDED : $order->payment_status,
            ]);

            return $order;
        });

        $this->notify($cancelled, 'Your order was cancelled');

        return $cancelled;
    }

    /**
     * Admin workflow: move an order along pending -> processing -> shipped
     * -> completed (or cancel it).
     *
     * @throws CheckoutException
     */
    public function updateStatus(Order $order, string $status): Order
    {
        if ($status === Order::STATUS_CANCELLED) {
            return $this->cancel($order);
        }

        $updated = DB::transaction(function () use ($order, $status) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($status, $order->next_statuses, true)) {
                throw new CheckoutException("An order that is {$order->status} can't be moved to {$status}.");
            }

            if (! $order->stock_deducted) {
                throw new CheckoutException('This order is still waiting for payment and cannot be processed yet.');
            }

            $attributes = ['status' => $status];

            if ($status === Order::STATUS_PROCESSING) {
                $attributes['processed_at'] = now();
            }

            if ($status === Order::STATUS_SHIPPED) {
                $attributes['shipped_at'] = now();
            }

            if ($status === Order::STATUS_COMPLETED) {
                $attributes['delivered_at'] = now();

                // Cash on delivery: the money is collected at the door.
                if ($order->isCod() && ! $order->isPaid()) {
                    $attributes['payment_status'] = Order::PAYMENT_PAID;
                }
            }

            $order->update($attributes);

            return $order;
        });

        $this->notify($updated, match ($status) {
            Order::STATUS_PROCESSING => 'Your order is being prepared',
            Order::STATUS_SHIPPED => 'Your order is on its way',
            default => 'Your order was delivered',
        });

        return $updated;
    }

    /**
     * Take stock out of inventory, consume the coupon, and empty the
     * purchased lines from the buyer's cart. Must run inside a transaction.
     *
     * @throws CheckoutException
     */
    protected function confirm(Order $order): void
    {
        $items = $order->items()->get();

        $products = Product::whereIn('id', $items->pluck('product_id')->filter())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::whereIn('id', $items->pluck('variant_id')->filter())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $variant = $item->variant_id ? $variants->get($item->variant_id) : null;
            $available = $item->variant_id ? ($variant?->stock ?? 0) : ($product?->stock ?? 0);

            if (! $product || ! $product->is_active || $available < $item->quantity) {
                throw new CheckoutException(
                    $item->product_name.' is out of stock or has fewer units than you ordered.'
                );
            }
        }

        $threshold = (int) config('shop.low_stock_threshold');

        foreach ($items as $item) {
            $product = $products[$item->product_id];
            $before = (int) $product->stock;

            // Never go below zero: the stock columns are UNSIGNED, and MySQL rejects a negative result.
            $product->forceFill(['stock' => max(0, $before - (int) $item->quantity)])->save();

            if ($item->variant_id) {
                $variant = $variants[$item->variant_id];
                $variant->forceFill(['stock' => max(0, (int) $variant->stock - (int) $item->quantity)])->save();
            }

            if ($before > $threshold && $product->stock <= $threshold) {
                $this->lowStock[$product->id] = $product;
            }
        }

        if ($order->coupon_code) {
            // Re-check under a lock: pending online orders may have been placed
            // with the same coupon before any of them was paid.
            $coupon = Coupon::where('code', $order->coupon_code)->lockForUpdate()->first();

            if ($coupon) {
                if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                    throw new CheckoutException('The coupon on this order has reached its usage limit.');
                }

                if ($coupon->once_per_user && Order::where('user_id', $order->user_id)
                    ->where('coupon_code', $coupon->code)
                    ->where('stock_deducted', true)
                    ->whereKeyNot($order->id)
                    ->exists()) {
                    throw new CheckoutException('You have already used the coupon on this order.');
                }

                $coupon->increment('used_count');
            }
        }

        // Only the lines that were bought leave the cart (same product but a
        // different variant may still be waiting there).
        foreach ($items as $item) {
            CartItem::where('user_id', $order->user_id)
                ->where('product_id', $item->product_id)
                ->where('variant_id', $item->variant_id)
                ->delete();
        }

        $order->update(['stock_deducted' => true]);
    }

    /**
     * @param  Collection<int, CartItem>  $cartItems
     *
     * @throws CheckoutException
     */
    protected function assertAvailable(Collection $cartItems): void
    {
        $products = Product::whereIn('id', $cartItems->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');

        foreach ($cartItems as $cartItem) {
            $product = $products->get($cartItem->product_id);

            if (! $product || ! $product->is_active || $product->stock < 1) {
                throw new CheckoutException(($product?->name ?? 'An item').' is no longer available. Please remove it to continue.');
            }

            $available = $cartItem->available_stock;

            if (! $cartItem->variant_id && $product->variants()->exists()) {
                throw new CheckoutException("Please choose an option for {$product->name} again. Remove it and add it from the product page.");
            }

            if ($cartItem->variant_id && ! $cartItem->variant) {
                throw new CheckoutException("The chosen option of {$product->name} is no longer available. Please remove it to continue.");
            }

            if ($available < 1) {
                throw new CheckoutException("{$product->name} ({$cartItem->variant_label}) is sold out. Please remove it to continue.");
            }

            if ($available < $cartItem->quantity) {
                throw new CheckoutException("Only {$available} of {$product->name} left. Please lower the quantity.");
            }
        }
    }

    /**
     * bKash/Nagad: the customer's transaction id (uppercased); empty for others.
     *
     * @param  array<string, mixed>  $shipping
     */
    protected function manualReference(string $method, array $shipping): ?string
    {
        if (! in_array($method, [Order::METHOD_BKASH, Order::METHOD_NAGAD], true)) {
            return null;
        }

        $reference = strtoupper(trim((string) ($shipping['transaction_id'] ?? '')));

        return $reference !== '' ? $reference : null;
    }

    /**
     * E-mail the admins about products that just ran low (best effort).
     */
    protected function flushLowStockAlerts(): void
    {
        if ($this->lowStock === []) {
            return;
        }

        $products = (new \Illuminate\Database\Eloquent\Collection($this->lowStock))->map->fresh()->filter()->values();
        $this->lowStock = [];

        try {
            $admins = User::where('role', 'admin')->where('is_blocked', false)->pluck('email');

            if ($admins->isNotEmpty() && $products->isNotEmpty()) {
                Mail::to($admins->all())->send(new LowStockMail($products));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function notify(Order $order, string $headline): void
    {
        try {
            Mail::to($order->user)->send(new OrderStatusMail($order->load('items'), $headline));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
