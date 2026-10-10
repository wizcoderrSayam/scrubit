<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ThrottleAuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_throttles_registration_after_ten_attempts_per_hour(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->post(route('register.store'), ['name' => ''])->assertRedirect();
        }

        $this->post(route('register.store'), ['name' => ''])->assertTooManyRequests();
    }

    public function test_throttles_password_reset_emails_for_the_same_address(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $attempt) {
            $this->post(route('password.email'), ['email' => 'someone@example.com'])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'someone@example.com'])->assertTooManyRequests();
    }

    public function test_does_not_throttle_unlisted_routes(): void
    {
        foreach (range(1, 15) as $attempt) {
            $this->get(route('home'))->assertOk();
        }
    }
}
