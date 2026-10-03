<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Vue pages build their URLs from numeric ids (see resources/js/routes.js).
 * These requests are made exactly the way the buttons make them, so a route that
 * only works with a slug (and 404s with an id) is caught here.
 */
class FrontendCallsTest extends TestCase
{
    use Concerns, RefreshDatabase;

    public function test_wishlist_add_and_remove_use_product_ids(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();
        $this->actingAs($user);

        $this->post('/wishlist', ['product_id' => $product->id])->assertSessionHas('success');
        $this->assertSame(1, Wishlist::where('user_id', $user->id)->count());

        // the "X" button on the wishlist page and the heart on a product card
        $this->delete("/wishlist/{$product->id}")->assertRedirect()->assertSessionHas('success');
        $this->assertSame(0, Wishlist::where('user_id', $user->id)->count());

        // removing something that is not there is harmless
        $this->delete("/wishlist/{$product->id}")->assertRedirect();
        $this->delete('/wishlist/999999')->assertNotFound();
    }

    public function test_wishlist_only_removes_the_current_users_entry(): void
    {
        $a = $this->makeUser('a@example.com');
        $b = $this->makeUser('b@example.com');
        $product = $this->makeProduct();
        Wishlist::create(['user_id' => $a->id, 'product_id' => $product->id]);
        Wishlist::create(['user_id' => $b->id, 'product_id' => $product->id]);

        $this->actingAs($a)->delete("/wishlist/{$product->id}");

        $this->assertSame(0, Wishlist::where('user_id', $a->id)->count());
        $this->assertSame(1, Wishlist::where('user_id', $b->id)->count());
    }

    public function test_cart_buttons_work_with_ids(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(['stock' => 10]);
        $item = $this->addToCart($user, $product, 1);
        $this->actingAs($user);

        $this->patch("/cart/{$item->id}", ['quantity' => 3])->assertSessionHasNoErrors();
        $this->assertSame(3, $item->fresh()->quantity);

        $this->delete("/cart/{$item->id}")->assertRedirect();
        $this->assertNull(CartItem::find($item->id));
    }

    public function test_address_buttons_work_with_ids(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->post('/account/addresses', [
            'label' => 'Home', 'name' => 'Rakib Hossain', 'phone' => '01712345678',
            'address' => 'House 12, Road 5, Dhanmondi', 'city' => 'Dhaka', 'is_default' => false,
        ])->assertSessionHasNoErrors();
        $address = Address::where('user_id', $user->id)->firstOrFail();

        $this->put("/account/addresses/{$address->id}", [
            'label' => 'Office', 'name' => 'Rakib Hossain', 'phone' => '01712345678',
            'address' => 'Level 4, Banani', 'city' => 'Dhaka', 'is_default' => false,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Office', $address->fresh()->label);

        $this->patch("/account/addresses/{$address->id}/default")->assertRedirect();
        $this->assertTrue((bool) $address->fresh()->is_default);

        $this->delete("/account/addresses/{$address->id}")->assertRedirect();
        $this->assertNull(Address::find($address->id));
    }

    public function test_a_user_cannot_touch_someone_elses_cart_or_address(): void
    {
        $owner = $this->makeUser('owner@example.com');
        $other = $this->makeUser('other@example.com');
        $item = $this->addToCart($owner, $this->makeProduct(), 1);
        $address = Address::create(['user_id' => $owner->id, 'label' => 'Home', 'name' => 'X', 'phone' => '01712345678', 'address' => 'Somewhere 1']);

        $this->actingAs($other);
        $this->delete("/cart/{$item->id}")->assertStatus(403);
        $this->delete("/account/addresses/{$address->id}")->assertStatus(403);
        $this->assertNotNull(CartItem::find($item->id));
        $this->assertNotNull(Address::find($address->id));
    }
}
