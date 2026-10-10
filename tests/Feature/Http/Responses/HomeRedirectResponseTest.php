<?php

namespace Tests\Feature\Http\Responses;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HomeRedirectResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Log in to your account');
    }

    public function test_redirects_administrators_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_redirects_customers_to_their_account(): void
    {
        $customer = User::factory()->create();

        $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_rejects_wrong_password(): void
    {
        $customer = User::factory()->create();

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $customer->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_throttles_after_five_failed_attempts(): void
    {
        $customer = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_returns_customer_to_the_page_they_originally_requested(): void
    {
        $customer = User::factory()->create();
        $this->get(route('account'))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('account'));
    }

    public function test_rejects_non_string_email_without_server_error(): void
    {
        $this->post(route('login.store'), ['email' => ['a@example.com'], 'password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    public function test_redirects_administrator_to_dashboard_after_email_verification(): void
    {
        $admin = User::factory()->admin()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->getKey(), 'hash' => sha1($admin->getEmailForVerification())],
        );

        $this->actingAs($admin)->get($verificationUrl)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotNull($admin->fresh()->email_verified_at);
    }
}
