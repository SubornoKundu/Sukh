<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_admin_buttons_sign_in_the_test_accounts(): void
    {
        $this->seed();

        $this->post('/auth/demo', ['provider' => 'customer'])->assertRedirect('/');
        $this->assertAuthenticatedAs(User::where('email', UserSeeder::USER_EMAIL)->first());
        $this->get('/admin')->assertForbidden();
        $this->post('/logout');

        $this->post('/auth/demo', ['provider' => 'admin'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs(User::where('email', UserSeeder::ADMIN_EMAIL)->first());
        $this->get('/admin')->assertOk();
    }

    public function test_buttons_work_even_when_the_test_accounts_are_missing_and_respect_blocks(): void
    {
        $this->post('/auth/demo', ['provider' => 'admin'])->assertRedirect('/admin');
        $this->assertSame('admin', auth()->user()->role);
        $this->post('/logout');

        User::where('email', UserSeeder::ADMIN_EMAIL)->update(['is_blocked' => true]);
        $this->post('/auth/demo', ['provider' => 'admin'])->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_switch_off_hides_everything(): void
    {
        $this->seed();
        config(['shop.demo_login' => false]);

        $this->post('/auth/demo', ['provider' => 'admin'])->assertNotFound();
        $this->actingAs(User::where('email', UserSeeder::ADMIN_EMAIL)->first())
            ->post('/admin/demo-reset')->assertNotFound();
    }

    public function test_only_admins_can_reset(): void
    {
        $this->seed();

        $this->post('/admin/demo-reset')->assertRedirect('/login');
        $this->actingAs(User::where('email', UserSeeder::USER_EMAIL)->first())
            ->post('/admin/demo-reset')->assertForbidden();
        $this->assertGreaterThan(0, Product::count());
    }

    public function test_reset_restores_sample_data_and_removes_uploaded_pictures(): void
    {
        $this->seed();

        // migrate:fresh cannot run inside the test's wrapping transaction on SQLite
        // (VACUUM), so step out of it. The schema is rebuilt empty in the finally block.
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $this->seed();

        $dir = public_path('images/uploads/products');

        try {
            $sampleProducts = Product::count();
            $sampleOrders = Order::count();

            // A visitor "messes up" the demo.
            Product::query()->delete();
            Order::query()->delete();
            Category::create(['name' => 'Visitor category', 'slug' => 'visitor-category']);
            User::where('email', 'rahim@shopease.test')->delete();

            File::ensureDirectoryExists($dir);
            File::put($dir.'/visitor-upload.jpg', 'x');

            $admin = User::where('email', UserSeeder::ADMIN_EMAIL)->first();
            $this->actingAs($admin)->post('/admin/demo-reset')
                ->assertRedirect('/admin')
                ->assertSessionHas('success');

            $this->assertGreaterThan(0, $sampleProducts);
            $this->assertSame($sampleProducts, Product::count());
            $this->assertSame($sampleOrders, Order::count());
            $this->assertDatabaseMissing('categories', ['slug' => 'visitor-category']);
            $this->assertDatabaseHas('users', ['email' => 'rahim@shopease.test']);
            $this->assertFileDoesNotExist($dir.'/visitor-upload.jpg');

            // Signed back in as the (new) admin, and the panel still opens.
            $this->assertAuthenticated();
            $this->assertSame(UserSeeder::ADMIN_EMAIL, auth()->user()->email);
            $this->get('/admin')->assertOk();
            $this->get('/')->assertOk();
        } finally {
            File::delete($dir.'/visitor-upload.jpg');
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
    }
}
