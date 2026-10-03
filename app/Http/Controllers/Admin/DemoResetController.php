<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Demo-site reset: puts the whole store back to its freshly seeded state
 * (database + pictures uploaded through the admin panel). Only available
 * while SHOP_DEMO_LOGIN is on -- turn that off on a real store.
 */
class DemoResetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('shop.demo_login'), 404);

        @set_time_limit(300);

        try {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        } catch (\Throwable $e) {
            Log::error('Demo reset failed: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Reset could not finish. Run "php artisan migrate:fresh --seed" once to repair the demo data.');
        }

        $this->clearUploads();

        try {
            Cache::flush();
        } catch (\Throwable) {
            // cache store may not support flushing; nothing else depends on it
        }

        // The users table was rebuilt, so sign the admin back in.
        $admin = User::where('email', UserSeeder::ADMIN_EMAIL)->first();

        if (! $admin) {
            return redirect()->route('login')->with('success', 'Demo data was reset. Please sign in again.');
        }

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Demo data reset. Everything is back to the original sample data.');
    }

    /**
     * Remove pictures uploaded through the admin panel (products, banners,
     * categories...). The bundled artwork lives outside this folder.
     */
    protected function clearUploads(): void
    {
        $root = public_path('images/uploads');

        if (! File::isDirectory($root)) {
            return;
        }

        foreach (File::directories($root) as $dir) {
            File::deleteDirectory($dir);
        }

        foreach (File::files($root) as $file) {
            if (! in_array($file->getFilename(), ['.gitkeep', '.htaccess'], true)) {
                File::delete($file->getPathname());
            }
        }
    }
}
