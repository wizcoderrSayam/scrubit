<?php

namespace Tests\Feature\Http\Controllers\Booking;

use App\Enums\BookingStatus;
use App\Models\AvailabilityException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Notifications\Admin\NewBookingNotification;
use App\Notifications\Customer\BookingReceivedNotification;
use App\Services\BookingDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private function startDraft(Service $service): array
    {
        $response = $this->get(route('booking.start', $service));
        $booking = Booking::query()->latest('id')->firstOrFail();
        $token = $response->getCookie(BookingDraftService::COOKIE)?->getValue();

        $response->assertRedirect(route('booking.customize', $booking));
        $this->assertNotNull($token);

        return [$booking, $token];
    }

    public function test_starting_a_booking_creates_a_draft_with_greeting_and_the_exact_addon_note(): void
    {
        $service = Service::factory()->create();
        [$booking, $token] = $this->startDraft($service);

        $this->assertSame(BookingStatus::Draft, $booking->status);
        $this->assertSame(2, $booking->messages()->count());

        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->get(route('booking.customize', $booking))
            ->assertOk()
            ->assertSee('Starting $25/hr add on services')
            ->assertSee($service->name);
    }

    public function test_revisiting_resumes_the_same_draft_and_keeps_messages(): void
    {
        $service = Service::factory()->create();
        [$booking, $token] = $this->startDraft($service);

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.messages.store', $booking), ['message' => 'Please focus on the kitchen and both bathrooms.'])
            ->assertOk();

        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->get(route('booking.start', $service))
            ->assertRedirect(route('booking.customize', $booking));

        $this->assertDatabaseCount(Booking::class, 1);
        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->get(route('booking.customize', $booking))
            ->assertSee('Please focus on the kitchen and both bathrooms.');
    }

    public function test_another_browser_cannot_open_the_draft(): void
    {
        [$booking] = $this->startDraft(Service::factory()->create());

        $this->get(route('booking.customize', $booking))->assertNotFound();
        $this->withCookie(BookingDraftService::COOKIE, str_repeat('z', 40))->withCredentials()
            ->postJson(route('booking.messages.store', $booking), ['message' => 'Hello there, I need a clean'])
            ->assertNotFound();
    }

    public function test_chat_walks_through_every_step_and_unlocks_review(): void
    {
        $service = Service::factory()->recurring()->create();
        [$booking, $token] = $this->startDraft($service);
        $chat = fn () => $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials();

        $chat()->postJson(route('booking.messages.store', $booking), ['message' => 'ok'])
            ->assertJsonPath('state.step', 'needs');
        $chat()->postJson(route('booking.messages.store', $booking), ['message' => 'Weekly upkeep focusing on the kitchen. Two bedroom condo, 900 sq ft, good condition.'])
            ->assertJsonPath('state.step', 'schedule');
        $chat()->postJson(route('booking.steps.store', [$booking, 'schedule']), [
            'preferred_date' => now()->addDays(5)->toDateString(),
            'time_window' => 'afternoon',
            'frequency' => 'weekly',
        ])->assertJsonPath('state.step', 'details');
        $chat()->postJson(route('booking.steps.store', [$booking, 'details']), [
            'line1' => '100 Granville St', 'city' => 'Vancouver', 'province' => 'BC', 'postal_code' => 'v6c 1t2',
            'contact_name' => 'Jamie Guest', 'contact_email' => 'Jamie@Example.com', 'contact_phone' => '604-555-0101',
        ])->assertJsonPath('state.step', 'extras');
        $chat()->postJson(route('booking.steps.store', [$booking, 'extras']), ['extra_request' => 'Clean inside the oven'])
            ->assertJsonPath('state.complete', true);

        $booking->refresh();
        $this->assertSame('V6C 1T2', $booking->address->postal_code);
        $this->assertSame('jamie@example.com', $booking->contact_email);
        $this->assertTrue($booking->addons()->where('needs_quote', true)->exists());

        $chat()->get(route('booking.review', $booking))
            ->assertOk()
            ->assertSee('Weekly upkeep focusing on the kitchen.')
            ->assertSee('Quote required');
    }

    public function test_schedule_rejects_closed_dates_and_dates_inside_the_lead_time(): void
    {
        [$booking, $token] = $this->startDraft(Service::factory()->create());
        $closed = now()->addDays(10)->toDateString();
        AvailabilityException::create(['date' => $closed, 'reason' => 'Holiday']);

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.steps.store', [$booking, 'schedule']), ['preferred_date' => $closed, 'time_window' => 'morning'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preferred_date');

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.steps.store', [$booking, 'schedule']), ['preferred_date' => now()->toDateString(), 'time_window' => 'morning'])
            ->assertJsonValidationErrors('preferred_date');
    }

    public function test_schedule_requires_a_frequency_for_recurring_services(): void
    {
        [$booking, $token] = $this->startDraft(Service::factory()->recurring()->create());

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.steps.store', [$booking, 'schedule']), [
                'preferred_date' => now()->addDays(5)->toDateString(),
                'time_window' => 'afternoon',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('frequency');
    }

    public function test_details_requires_both_address_and_contact_fields_together(): void
    {
        [$booking, $token] = $this->startDraft(Service::factory()->create());

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.steps.store', [$booking, 'details']), [
                'line1' => '100 Granville St', 'city' => 'Vancouver', 'province' => 'BC', 'postal_code' => 'V6C 1T2',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contact_name', 'contact_email', 'contact_phone']);
    }

    public function test_review_redirects_back_to_chat_until_requirements_are_complete(): void
    {
        [$booking, $token] = $this->startDraft(Service::factory()->create());

        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->get(route('booking.review', $booking))
            ->assertRedirect(route('booking.customize', $booking));
    }

    public function test_submit_requires_accepting_terms(): void
    {
        $booking = $this->completedDraft();

        $this->withCookie(BookingDraftService::COOKIE, $this->draftCookieToken())
            ->from(route('booking.review', $booking))
            ->post(route('booking.submit', $booking))
            ->assertSessionHasErrors(['accept_terms' => 'Please accept the Terms and Conditions and Privacy Policy to continue.']);

        $this->assertSame(BookingStatus::Draft, $booking->fresh()->status);
    }

    public function test_submitting_a_quote_service_moves_to_pending_quote_and_notifies(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $booking = $this->completedDraft();

        $response = $this->withCookie(BookingDraftService::COOKIE, $this->draftCookieToken())
            ->post(route('booking.submit', $booking), ['accept_terms' => '1']);

        $booking->refresh();
        $response->assertRedirectContains(route('bookings.show', $booking));
        $this->assertSame(BookingStatus::PendingQuote, $booking->status);
        $this->assertNull($booking->draft_token_hash);
        $this->assertNotNull($booking->access_token_hash);
        $this->assertSame(0, $booking->total_cents);
        Notification::assertSentOnDemand(BookingReceivedNotification::class);
        Notification::assertSentOnDemand(NewBookingNotification::class);
        Notification::assertSentTo(User::query()->admins()->first(), NewBookingNotification::class);
        Notification::assertCount(3);

        $this->get(route('bookings.show', $booking))->assertOk()->assertSee('quote pending');
    }

    public function test_submitted_prices_come_from_the_server_not_the_request(): void
    {
        Notification::fake();
        $booking = $this->completedDraft(Service::factory()->fixed(18000)->create());

        $this->withCookie(BookingDraftService::COOKIE, $this->draftCookieToken())
            ->post(route('booking.submit', $booking), ['accept_terms' => '1', 'total_cents' => 1, 'subtotal_cents' => 1, 'quote_required' => '1'])
            ->assertRedirect(route('checkout.show', $booking));

        $booking->refresh();
        $this->assertSame(BookingStatus::AwaitingPayment, $booking->status);
        $this->assertSame(18000, $booking->total_cents);
        $this->assertSame(18000, $booking->unit_price_cents);
    }

    public function test_guest_draft_is_claimed_when_the_customer_logs_in(): void
    {
        $service = Service::factory()->create();
        [$booking, $token] = $this->startDraft($service);
        $customer = User::factory()->create();

        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->post(route('login.store'), ['email' => $customer->email, 'password' => 'password']);

        $booking->refresh();
        $this->assertSame($customer->id, $booking->user_id);
        $this->assertNull($booking->draft_token_hash);
        $this->actingAs($customer)->get(route('booking.customize', $booking))->assertOk();
    }

    public function test_chat_messages_are_escaped_when_rendered(): void
    {
        [$booking, $token] = $this->startDraft(Service::factory()->create());

        $this->withCookie(BookingDraftService::COOKIE, $token)->withCredentials()
            ->postJson(route('booking.messages.store', $booking), ['message' => '<script>alert("x")</script> please clean'])
            ->assertOk();

        $this->withCookie(BookingDraftService::COOKIE, $token)
            ->get(route('booking.customize', $booking))
            ->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_inactive_services_cannot_be_booked(): void
    {
        $this->get(route('booking.start', Service::factory()->inactive()->create()))->assertNotFound();
    }
}
