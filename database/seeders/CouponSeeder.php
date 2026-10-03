<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            ['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'min_order_amount' => 1000, 'max_discount' => 1500, 'once_per_user' => true],
            ['code' => 'SAVE200', 'type' => 'fixed', 'value' => 200, 'min_order_amount' => 2000, 'max_discount' => null, 'once_per_user' => false],
            ['code' => 'MEGA25', 'type' => 'percent', 'value' => 25, 'min_order_amount' => 20000, 'max_discount' => 10000, 'once_per_user' => true],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(['code' => $coupon['code']], $coupon + ['is_active' => true]);
        }
    }
}
