<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@shopease.test';

    public const ADMIN_PASSWORD = 'Admin@12345';

    public const USER_EMAIL = 'user@shopease.test';

    public const USER_PASSWORD = 'User@12345';

    /**
     * [name, email, phone] -- extra customers so the admin panel has
     * something to show. They share the test user's password.
     */
    public const SAMPLE_CUSTOMERS = [
        ['Rahim Uddin', 'rahim@shopease.test', '01711111111'],
        ['Sadia Islam', 'sadia@shopease.test', '01822222222'],
        ['Tanvir Ahmed', 'tanvir@shopease.test', '01933333333'],
    ];

    public function run(): void
    {
        // The test accounts are for local/demo use. Change (or delete) them
        // before putting a real store online.
        $this->account('Test Admin', self::ADMIN_EMAIL, '01700000001', self::ADMIN_PASSWORD, 'admin');
        $this->account('Test User', self::USER_EMAIL, '01700000000', self::USER_PASSWORD, 'customer');

        foreach (self::SAMPLE_CUSTOMERS as [$name, $email, $phone]) {
            $this->account($name, $email, $phone, self::USER_PASSWORD, 'customer');
        }
    }

    protected function account(string $name, string $email, string $phone, string $password, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->exists ? $user->name : $name;
        // The phone column is unique, so skip it if another account (for
        // example an older demo user) already uses this number.
        if (! $user->phone && ! User::where('phone', $phone)->where('email', '!=', $email)->exists()) {
            $user->phone = $phone;
        }
        $user->password = $password;
        $user->address ??= 'House 12, Road 5, Dhanmondi';
        $user->city ??= 'Dhaka';
        $user->forceFill(['role' => $role, 'is_blocked' => false, 'email_verified_at' => now()])->save();

        return $user;
    }
}
