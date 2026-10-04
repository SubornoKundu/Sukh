<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoBaseline;
use Database\Seeders\UserSeeder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Demo-site reset. If a saved demo state exists (Admin > "Save as demo state") the
 * whole store goes back to exactly that: database + uploaded pictures. Without one it
 * goes back to the freshly seeded sample data. Only available while SHOP_DEMO_LOGIN
 * is on -- turn that off on a real store.
 */
class DemoResetController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('shop.demo_login'), 404);

        @set_time_limit(300);

        if (DemoBaseline::exists()) {
            return $this->restoreSaved($request);
        }

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
     * Called by a cron job (cPanel > Cron Jobs) so the demo cleans itself up, e.g. every night.
     * Needs DEMO_RESET_TOKEN in .env; answers "not found" for anyone without it.
     */
    public function auto(Request $request): Response
    {
        $token = (string) config('shop.demo_reset_token');
        $given = (string) ($request->input('token') ?? $request->header('X-Reset-Token', ''));

        abort_unless(config('shop.demo_login') && $token !== '' && hash_equals($token, $given), 404);

        if (! DemoBaseline::exists()) {
            return response('No saved demo state yet. Save one from the admin panel first.', 409);
        }

        try {
            DemoBaseline::restore();
        } catch (\Throwable $e) {
            Log::error('Automatic demo reset failed: '.$e->getMessage());

            return response('Reset failed: '.$e->getMessage(), 500);
        }

        return response('OK', 200);
    }

    private function restoreSaved(Request $request): RedirectResponse
    {
        try {
            DemoBaseline::restore();
        } catch (\Throwable $e) {
            Log::error('Demo reset failed: '.$e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        // The users table was reloaded. Stay signed in as the same admin when they are still one.
        $user = Auth::id() ? User::find(Auth::id()) : null;

        if (! $user?->isAdmin()) {
            $user = User::where('email', UserSeeder::ADMIN_EMAIL)->first();
        }

        if (! $user?->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Demo data was reset. Please sign in again.');
        }

        Auth::setUser($user);

        return redirect()->route('admin.dashboard')->with('success', 'Demo reset. Everything is back to the saved demo state.');
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
