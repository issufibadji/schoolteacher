<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create (or update) the initial admin user from ADMIN_* env vars.
     * Safe to run repeatedly; the password is only set on creation.
     */
    public function run(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('AdminUserSeeder: ADMIN_EMAIL/ADMIN_PASSWORD não definidos, pulando.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => config('app.admin.name'),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'active' => true,
        ]);

        if (! $user->exists) {
            $user->password = Hash::make($password);
        }

        $user->save();
        $user->syncRoles(['admin']);
    }
}
