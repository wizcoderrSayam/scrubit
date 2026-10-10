<?php

namespace Tests\Feature\Actions\Fortify;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Jamie Customer',
            'email' => 'jamie@example.com',
            'phone' => '+1 (604) 555-0123',
            'password' => 'a-strong-password-123',
            'password_confirmation' => 'a-strong-password-123',
        ];
    }

    public function test_registration_creates_customer_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), $this->validPayload())
            ->assertRedirect(route('account'));

        $user = User::query()->where('email', 'jamie@example.com')->sole();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('+1 (604) 555-0123', $user->phone);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_ignores_submitted_role(): void
    {
        $this->post(route('register.store'), [...$this->validPayload(), 'role' => 'admin']);

        $this->assertSame(UserRole::Customer, User::query()->where('email', 'jamie@example.com')->sole()->role);
    }

    public function test_registration_rejects_invalid_phone_number(): void
    {
        $this->post(route('register.store'), [...$this->validPayload(), 'phone' => 'call me maybe'])
            ->assertSessionHasErrors(['phone']);

        $this->assertDatabaseCount(User::class, 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'jamie@example.com']);

        $this->post(route('register.store'), $this->validPayload())
            ->assertSessionHasErrors(['email' => 'The email has already been taken.']);
    }
}
