<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Database\Seeders\UserSeeder;

/**
 * One-click sign in for demos: a shared "demo customer" account, and a
 * simulated Google One-Tap account. No real Google call is made -- swap this
 * controller for Laravel Socialite when you get a Google client id.
 */
class DemoLoginController extends Controller
{
    /** @var array<string, array{name: string, email: string}> */
    protected const ACCOUNTS = [
        'demo' => ['name' => 'Demo Customer', 'email' => 'demo.customer@shopease.test'],
        'google' => ['name' => 'Google Demo User', 'email' => 'google.demo@shopease.test'],
        // The seeded test accounts, used by the two "Enter as" buttons.
        'customer' => ['name' => 'Test User', 'email' => UserSeeder::USER_EMAIL, 'role' => 'customer'],
        'admin' => ['name' => 'Test Admin', 'email' => UserSeeder::ADMIN_EMAIL, 'role' => 'admin'],
    ];

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('shop.demo_login'), 404);

        $data = $request->validate(['provider' => ['required', 'in:demo,google,customer,admin']]);
        $account = self::ACCOUNTS[$data['provider']];

        $user = User::firstOrNew(['email' => $account['email']]);

        if (! $user->exists) {
            $user->name = $account['name'];
            $user->password = Str::random(40);
            $user->forceFill(['role' => $account['role'] ?? 'customer', 'is_blocked' => false, 'email_verified_at' => now()])->save();
        }

        if ($user->is_blocked || $user->role !== ($account['role'] ?? 'customer')) {
            return back()->with('error', 'This demo account is not available.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('success', 'Signed in as '.$user->name.' (demo).');
        }

        return redirect()->intended(route('home'))->with('success', 'Signed in as '.$user->name.' (demo).');
    }
}
