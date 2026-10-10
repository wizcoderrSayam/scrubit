<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_verified_admin_from_configuration(): void
    {
        config(['scrubit.admin.email' => 'owner@example.com', 'scrubit.admin.password' => 'secret-password']);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'owner@example.com')->sole();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $admin->password));
    }

    public function test_never_modifies_an_existing_account_with_the_same_email(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.com']);
        $originalPasswordHash = $existing->password;
        config(['scrubit.admin.email' => 'owner@example.com', 'scrubit.admin.password' => 'secret-password']);

        $this->seed(AdminUserSeeder::class);

        $existing->refresh();
        $this->assertDatabaseCount(User::class, 1);
        $this->assertSame(UserRole::Customer, $existing->role);
        $this->assertSame($originalPasswordHash, $existing->password);
    }

    public function test_skips_when_credentials_are_not_configured(): void
    {
        config(['scrubit.admin.email' => null, 'scrubit.admin.password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount(User::class, 0);
    }
}
