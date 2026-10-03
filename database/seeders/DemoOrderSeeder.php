<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOrderSeeder extends Seeder
{
    /**
     * Order history for the test user (user@shopease.test), so the "My
     * Orders" screen -- and the admin order list -- have something to show
     * without first placing a live order.
     */
    public const DEMO_EMAIL = UserSeeder::USER_EMAIL;

    /**
     * [[slug => qty, ...], status, days ago]
     *
     * @var list<array{0: array<string, int>, 1: string, 2: int}>
     */
    public const ORDERS = [
        [['iphone-15-pro-max' => 1, 'airpods-pro-2' => 1], Order::STATUS_COMPLETED, 6],
        [['air-max-270' => 1], Order::STATUS_PROCESSING, 1],
        [['mi-band-8' => 2], Order::STATUS_COMPLETED, 12],
        [['dri-fit-running-tee' => 2, 'heritage-backpack' => 1], Order::STATUS_COMPLETED, 9],
        [['training-yoga-mat' => 1], Order::STATUS_COMPLETED, 4],
        [['samba-og' => 1], Order::STATUS_SHIPPED, 3],
        [['robot-vacuum-x10-plus' => 1], Order::STATUS_PROCESSING, 2],
        [['redmi-note-13-pro' => 1], Order::STATUS_PENDING, 0],
    ];

    public function run(): void
    {
        $this->call(UserSeeder::class);

        $user = User::where('email', self::DEMO_EMAIL)->firstOrFail();

        if ($user->orders()->exists()) {
            return;
        }

        foreach (self::ORDERS as [$lines, $status, $daysAgo]) {
            $subtotal = 0;
            $items = [];

            foreach ($lines as $slug => $quantity) {
                $product = Product::where('slug', $slug)->first();

                if (! $product) {
                    continue;
                }

                $lineSubtotal = round($product->final_price * $quantity, 2);
                $subtotal += $lineSubtotal;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'price' => $product->final_price,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (empty($items)) {
                continue;
            }

            $shippingFee = config('shop.shipping_fee');
            $placedAt = now()->subDays($daysAgo);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => round($subtotal + $shippingFee, 2),
                'status' => $status,
                'payment_status' => Order::PAYMENT_PAID,
                'payment_method' => $status === Order::STATUS_COMPLETED ? 'bkash' : 'card',
                'stock_deducted' => true,
                'processed_at' => $status === Order::STATUS_PENDING ? null : $placedAt->copy()->addHour(),
                'shipped_at' => in_array($status, [Order::STATUS_SHIPPED, Order::STATUS_COMPLETED], true) ? $placedAt->copy()->addDay() : null,
                'delivered_at' => $status === Order::STATUS_COMPLETED ? $placedAt->copy()->addDays(3) : null,
                'transaction_id' => strtoupper('DEMO'.$placedAt->format('ymdHis').bin2hex(random_bytes(3))),
                'shipping_name' => $user->name,
                'shipping_phone' => $user->phone ?: '01700000000',
                'shipping_address' => 'House 12, Road 5, Dhanmondi',
                'shipping_city' => 'Dhaka',
            ]);

            // created_at/updated_at aren't mass-assignable (by design --
            // see Order::$fillable), so backdating this demo order's
            // "placed on" date needs an explicit forceFill().
            $order->forceFill([
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ])->save();

            foreach ($items as $item) {
                OrderItem::create([...$item, 'order_id' => $order->id]);
            }
        }
    }
}
