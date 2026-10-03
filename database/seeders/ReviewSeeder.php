<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * [user email, product slug, rating, comment]
     */
    public const REVIEWS = [
        [UserSeeder::USER_EMAIL, 'iphone-15-pro-max', 5, 'Fast, gorgeous screen and the camera is superb. Arrived well packed.'],
        [UserSeeder::USER_EMAIL, 'airpods-pro-2', 4, 'Great noise cancellation. Battery life is good too.'],
        ['rahim@shopease.test', 'iphone-15-pro-max', 4, 'Love it, only wish the charger was included.'],
        ['sadia@shopease.test', 'air-max-270', 5, 'Super comfortable and true to size.'],
        ['tanvir@shopease.test', 'galaxy-s24-ultra', 5, 'The best Android phone I have owned.'],
    ];

    public function run(): void
    {
        foreach (self::REVIEWS as [$email, $slug, $rating, $comment]) {
            $user = User::where('email', $email)->first();
            $product = Product::where('slug', $slug)->first();

            if ($user && $product) {
                Review::updateOrCreate(
                    ['user_id' => $user->id, 'product_id' => $product->id],
                    ['rating' => $rating, 'comment' => $comment, 'is_approved' => true],
                );
            }
        }
    }
}
