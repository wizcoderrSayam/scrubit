<?php

namespace Tests\Feature\Http\Controllers\Customer;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
    }

    public function test_unverified_customer_is_redirected_to_verification_notice(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('account'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_customer_sees_their_profile(): void
    {
        $customer = User::factory()->create(['name' => 'Jamie Customer', 'phone' => '604-555-0123']);

        $this->actingAs($customer)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Jamie Customer')
            ->assertSee('604-555-0123');
    }

    public function test_customer_can_update_contact_details(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->from(route('account'))
            ->put(route('user-profile-information.update'), [
                'name' => 'Updated Name',
                'email' => $customer->email,
                'phone' => '+1 778 555 0199',
            ])
            ->assertRedirect(route('account'))
            ->assertSessionHas('status', 'profile-information-updated');

        $customer->refresh();
        $this->assertSame('Updated Name', $customer->name);
        $this->assertSame('+1 778 555 0199', $customer->phone);
    }

    public function test_changing_email_requires_verifying_the_new_address(): void
    {
        Notification::fake();
        $customer = User::factory()->create();

        $this->actingAs($customer)->put(route('user-profile-information.update'), [
            'name' => $customer->name,
            'email' => 'new-address@example.com',
        ]);

        $customer->refresh();
        $this->assertSame('new-address@example.com', $customer->email);
        $this->assertNull($customer->email_verified_at);
        Notification::assertSentTo($customer, VerifyEmail::class);
        $this->get(route('account'))->assertRedirect(route('verification.notice'));
    }
}
