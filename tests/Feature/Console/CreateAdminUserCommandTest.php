<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_verified_admin_from_options(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => 'owner@example.com',
            '--password' => 'secret-password',
        ])->assertSuccessful();

        $admin = User::query()->where('email', 'owner@example.com')->sole();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame('Owner', $admin->name);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $admin->password));
    }

    public function test_prompts_for_missing_values(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Email', 'owner@example.com')
            ->expectsQuestion('Password', 'secret-password')
            ->expectsQuestion('Confirm password', 'secret-password')
            ->assertSuccessful();

        $this->assertTrue(User::query()->where('email', 'owner@example.com')->sole()->isAdmin());
    }

    public function test_fails_when_prompted_passwords_do_not_match(): void
    {
        $this->artisan('admin:create', ['--name' => 'Owner', '--email' => 'owner@example.com'])
            ->expectsQuestion('Password', 'secret-password')
            ->expectsQuestion('Confirm password', 'different-password')
            ->assertFailed();

        $this->assertDatabaseCount(User::class, 0);
    }

    public function test_fails_with_invalid_email(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => 'not-an-email',
            '--password' => 'secret-password',
        ])->assertFailed();

        $this->assertDatabaseCount(User::class, 0);
    }

    public function test_promotes_existing_user_after_confirmation(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => 'owner@example.com',
            '--password' => 'new-secret-password',
        ])
            ->expectsConfirmation('A user with owner@example.com already exists. Make them an admin, set their name to Owner and reset their password?', 'yes')
            ->assertSuccessful();

        $this->assertDatabaseCount(User::class, 1);
        $this->assertSame(UserRole::Admin, $existing->fresh()->role);
        $this->assertTrue(Hash::check('new-secret-password', $existing->fresh()->password));
    }

    public function test_normalizes_email_before_matching_existing_user(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => '  Owner@Example.com ',
            '--password' => 'new-secret-password',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount(User::class, 1);
    }

    public function test_leaves_existing_user_untouched_when_declined(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => 'owner@example.com',
            '--password' => 'new-secret-password',
        ])
            ->expectsConfirmation('A user with owner@example.com already exists. Make them an admin, set their name to Owner and reset their password?', 'no')
            ->assertFailed();

        $this->assertSame(UserRole::Customer, $existing->fresh()->role);
    }

    public function test_force_updates_existing_user_without_asking(): void
    {
        $existing = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('admin:create', [
            '--name' => 'Owner',
            '--email' => 'owner@example.com',
            '--password' => 'new-secret-password',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(UserRole::Admin, $existing->fresh()->role);
    }
}
