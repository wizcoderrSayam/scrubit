<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the initial administrator from ADMIN_EMAIL / ADMIN_PASSWORD.
     *
     * Safe to run repeatedly: an existing account with that email is never
     * modified, so re-seeding cannot reset a live password or promote a customer.
     */
    public function run(): void
    {
        $email = config('scrubit.admin.email');
        $password = config('scrubit.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Skipping admin user: set ADMIN_EMAIL and ADMIN_PASSWORD in .env.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Admin seeding skipped: an account for {$email} already exists.");

            return;
        }

        $admin = new User;
        $admin->forceFill([
            'name' => config('scrubit.admin.name'),
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ])->save();
    }
}
