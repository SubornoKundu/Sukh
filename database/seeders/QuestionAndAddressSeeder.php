<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionAndAddressSeeder extends Seeder
{
    /**
     * [user email, product slug, question, answer|null]
     */
    public const QUESTIONS = [
        ['rahim@shopease.test', 'iphone-15-pro-max', 'Is this the international version with an official warranty?', 'Yes, it is the official-warranty unit with a 1 year brand warranty.'],
        ['sadia@shopease.test', 'iphone-15-pro-max', 'Does the box include a charging adapter?', 'The box has a USB-C cable only. The power adapter is sold separately.'],
        ['tanvir@shopease.test', 'air-max-270', 'Do these run true to size? I usually wear 42.', 'They run true to size. If you are between sizes we suggest going one size up.'],
        [UserSeeder::USER_EMAIL, 'galaxy-s24-ultra', 'Is the S Pen included in the box?', null],
        ['rahim@shopease.test', 'airpods-pro-2', 'Do they work with Android phones?', null],
    ];

    public function run(): void
    {
        $this->call(UserSeeder::class);

        foreach (self::QUESTIONS as [$email, $slug, $question, $answer]) {
            $user = User::where('email', $email)->first();
            $product = Product::where('slug', $slug)->first();

            if (! $user || ! $product) {
                continue;
            }

            ProductQuestion::updateOrCreate(
                ['user_id' => $user->id, 'product_id' => $product->id, 'question' => $question],
                [
                    'answer' => $answer,
                    'answered_at' => $answer ? now()->subDays(2) : null,
                    'is_published' => true,
                ],
            );
        }

        $user = User::where('email', UserSeeder::USER_EMAIL)->first();

        if ($user && ! $user->addresses()->exists()) {
            $user->addresses()->createMany([
                ['label' => 'Home', 'name' => $user->name, 'phone' => '01700000000', 'address' => 'House 12, Road 5, Dhanmondi', 'city' => 'Dhaka', 'is_default' => true],
                ['label' => 'Office', 'name' => $user->name, 'phone' => '01700000000', 'address' => 'Level 4, Banani Tower, Road 11, Banani', 'city' => 'Dhaka', 'is_default' => false],
            ]);
        }
    }
}
