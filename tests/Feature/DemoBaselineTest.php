<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\DemoBaseline;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DemoBaselineTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail();
    }

    private function upload(string $name, string $body = 'pic'): void
    {
        File::ensureDirectoryExists(DemoBaseline::uploadsPath().'/products');
        File::put(DemoBaseline::uploadsPath().'/products/'.$name, $body);
    }

    public function test_reset_brings_back_exactly_the_saved_state(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);

        // The way the boss sets the demo up.
        DB::table('settings')->updateOrInsert(['key' => 'site_name'], ['value' => 'Demo Store']);
        $category = Category::create(['name' => 'Boss Category', 'slug' => 'boss-category']);
        $this->upload('boss.jpg', 'boss-picture');

        $products = Product::count();
        $users = User::count();

        $this->actingAs($this->admin())->post('/admin/demo-baseline')->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertTrue(DemoBaseline::exists());

        // A visitor messes everything up.
        Product::query()->delete();
        User::where('email', 'rahim@shopease.test')->delete();
        DB::table('orders')->delete();
        $category->delete();
        Category::create(['name' => 'Visitor Category', 'slug' => 'visitor-category']);
        DB::table('settings')->where('key', 'site_name')->update(['value' => 'Hacked']);
        File::delete(DemoBaseline::uploadsPath().'/products/boss.jpg');
        $this->upload('visitor.jpg');

        $this->actingAs($this->admin())->post('/admin/demo-reset')
            ->assertRedirect('/admin')
            ->assertSessionHas('success');

        $this->assertSame($products, Product::count());
        $this->assertSame($users, User::count());
        $this->assertDatabaseHas('categories', ['slug' => 'boss-category']);
        $this->assertDatabaseMissing('categories', ['slug' => 'visitor-category']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Demo Store']);
        $this->assertDatabaseHas('users', ['email' => 'rahim@shopease.test']);
        $this->assertGreaterThan(0, DB::table('orders')->count());
        $this->assertSame('boss-picture', File::get(DemoBaseline::uploadsPath().'/products/boss.jpg'));
        $this->assertFileDoesNotExist(DemoBaseline::uploadsPath().'/products/visitor.jpg');

        // Still signed in as the admin, the panel and the shop open, and new rows can be added.
        $this->assertAuthenticated();
        $this->assertSame(UserSeeder::ADMIN_EMAIL, auth()->user()->email);
        $this->get('/admin')->assertOk();
        $this->get('/')->assertOk();
        $this->assertNotNull(Category::create(['name' => 'After', 'slug' => 'after'])->id);
    }

    public function test_resetting_twice_gives_the_same_result(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $products = Product::count();

        $this->actingAs($this->admin())->post("/admin/demo-baseline");

        foreach ([1, 2] as $round) {
            Product::query()->delete();
            $this->actingAs($this->admin())->post('/admin/demo-reset')->assertSessionHas('success');
            $this->assertSame($products, Product::count(), "round {$round}");
        }
    }

    public function test_a_damaged_saved_copy_changes_nothing(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $this->actingAs($this->admin())->post('/admin/demo-baseline');

        $file = DemoBaseline::path('data.jsonl');
        file_put_contents($file, file_get_contents($file).'{this is not json'."\n");

        $before = Product::count();
        Product::where('id', Product::value('id'))->delete();

        $this->actingAs($this->admin())->post('/admin/demo-reset')
            ->assertSessionHas('error');

        $this->assertSame($before - 1, Product::count(), 'the site was left as it was');
        $this->assertAuthenticated();
    }

    public function test_a_missing_picture_in_the_saved_copy_changes_nothing(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $this->upload('one.jpg');
        $this->actingAs($this->admin())->post('/admin/demo-baseline');

        File::delete(DemoBaseline::path('uploads/products/one.jpg'));
        Product::query()->delete();

        $this->actingAs($this->admin())->post('/admin/demo-reset')->assertSessionHas('error');

        $this->assertSame(0, Product::count());
    }

    public function test_without_a_saved_copy_reset_still_works_like_before(): void
    {
        config(['shop.demo_login' => true]);
        $this->assertFalse(DemoBaseline::exists());

        $this->seed();
        // migrate:fresh cannot run inside the test's wrapping transaction on SQLite.
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        $this->seed();

        try {
            Product::query()->delete();
            $this->actingAs($this->admin())->post('/admin/demo-reset')->assertRedirect('/admin');
            $this->assertGreaterThan(0, Product::count());
        } finally {
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
    }

    public function test_only_the_owner_can_save_and_never_on_a_live_site(): void
    {
        $this->seed();

        // Settings closed (what the client sees): the save address does not exist.
        config(['shop.demo_login' => true, 'shop.settings_access' => false]);
        $this->actingAs($this->admin())->post('/admin/demo-baseline')->assertNotFound();
        $this->assertFalse(DemoBaseline::exists());

        // A customer is refused whatever the settings.
        config(['shop.settings_access' => true]);
        $this->actingAs(User::where('email', UserSeeder::USER_EMAIL)->firstOrFail())->post('/admin/demo-baseline')->assertForbidden();

        // Not a demo site at all.
        config(['shop.demo_login' => false]);
        $this->actingAs($this->admin())->post('/admin/demo-baseline')->assertNotFound();
        $this->assertFalse(DemoBaseline::exists());
    }

    public function test_save_button_shows_only_with_settings_open_and_reset_text_follows_the_saved_state(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);

        $page = fn () => $this->actingAs($this->admin())->get('/admin')->viewData('page')['props']['shop'];

        config(['shop.settings_access' => true]);
        $this->assertNull($page()['demoSavedAt']);

        $this->actingAs($this->admin())->post('/admin/demo-baseline');
        $this->assertNotNull($page()['demoSavedAt']);

        // Customers never get the saved time.
        $shop = $this->actingAs(User::where('email', UserSeeder::USER_EMAIL)->firstOrFail())->get('/')->viewData('page')['props']['shop'];
        $this->assertNull($shop['demoSavedAt']);
    }

    public function test_cron_url_needs_the_secret_and_a_saved_copy(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $products = Product::count();

        // No token configured: closed. Wrong token: closed.
        $this->get('/demo-auto-reset?token=anything')->assertNotFound();
        config(['shop.demo_reset_token' => 's3cret-token']);
        $this->get('/demo-auto-reset')->assertNotFound();
        $this->get('/demo-auto-reset?token=wrong')->assertNotFound();

        // Right token but nothing saved yet.
        $this->get('/demo-auto-reset?token=s3cret-token')->assertStatus(409);

        $this->actingAs($this->admin())->post('/admin/demo-baseline');
        auth()->logout();
        Product::query()->delete();

        $this->get('/demo-auto-reset?token=s3cret-token')->assertOk()->assertSee('OK');
        $this->assertSame($products, Product::count());

        // cron tools that POST (no CSRF cookie) work too
        Product::query()->delete();
        $this->post('/demo-auto-reset', ['token' => 's3cret-token'])->assertOk();
        $this->assertSame($products, Product::count());

        // Never on a real shop.
        config(['shop.demo_login' => false]);
        $this->get('/demo-auto-reset?token=s3cret-token')->assertNotFound();
    }

    public function test_artisan_command_saves_and_restores(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $products = Product::count();

        $this->artisan('demo:reset')->assertFailed(); // nothing saved yet
        $this->artisan('demo:reset --save')->assertSuccessful();

        Product::query()->delete();
        $this->artisan('demo:reset')->assertSuccessful();
        $this->assertSame($products, Product::count());

        config(['shop.demo_login' => false]);
        $this->artisan('demo:reset')->assertFailed();
    }

    public function test_columns_that_changed_since_saving_do_not_break_the_restore(): void
    {
        $this->seed();
        config(['shop.demo_login' => true]);
        $this->actingAs($this->admin())->post('/admin/demo-baseline');

        // Pretend the saved copy came from a version that had an extra column and an extra table.
        $file = DemoBaseline::path('data.jsonl');
        $lines = explode("\n", trim(file_get_contents($file)));
        $out = [];
        $inCategories = false;

        foreach ($lines as $line) {
            $data = json_decode($line, true);

            if (! array_is_list($data)) {
                $inCategories = $data['table'] === 'categories';

                if ($inCategories) {
                    $data['columns'][] = 'removed_column';
                }

                $line = json_encode($data);
            } elseif ($inCategories) {
                $data[] = 'old value';
                $line = json_encode($data);
            }

            $out[] = $line;
        }

        file_put_contents($file, implode("\n", $out)."\n");

        Product::query()->delete();
        Category::query()->delete();
        $this->actingAs($this->admin())->post('/admin/demo-reset')->assertSessionHas('success');

        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, Product::count());
    }

    public function test_a_listed_table_that_cannot_be_opened_does_not_stop_save_or_reset(): void
    {
        $this->seed();

        // Some databases list a leftover table ("payments") that errors with "doesn't exist".
        // Opening such a table must count as "nothing to save", a normal table as readable.
        $readable = new \ReflectionMethod(DemoBaseline::class, 'readable');

        $tables = (new \ReflectionMethod(DemoBaseline::class, 'tables'))->invoke(null);
        $this->assertSame($tables, array_values(array_unique($tables)));

        $this->assertTrue($readable->invoke(null, 'products'));
        $this->assertFalse($readable->invoke(null, 'payments'));

        // The normal save and reset still work and never mention the unreadable name.
        $meta = DemoBaseline::save();

        $this->assertArrayNotHasKey('payments', $meta['tables']);
        $this->assertArrayHasKey('products', $meta['tables']);

        Product::query()->delete();
        DemoBaseline::restore();

        $this->assertGreaterThan(0, Product::count());
    }

    public function test_a_saved_copy_with_a_table_twice_is_refused_and_changes_nothing(): void
    {
        $this->seed();
        DemoBaseline::save();

        $file = DemoBaseline::path('data.jsonl');
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $header = null;

        foreach ($lines as $line) {
            if (str_contains($line, '"table":"addresses"')) {
                $header = $line;
            }
        }

        // Repeat the whole header of a table (the shape of the old bug) at the end.
        file_put_contents($file, $header."\n", FILE_APPEND);

        $before = Product::count();

        try {
            DemoBaseline::restore();
            $this->fail('A damaged copy must be refused.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Nothing was changed', $e->getMessage());
        }

        $this->assertSame($before, Product::count());
    }
}
