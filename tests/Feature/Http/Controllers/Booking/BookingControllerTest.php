<?php

namespace Tests\Feature\Http\Controllers\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Admin\CancellationRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    public function test_guessing_a_reference_without_a_token_returns_404(): void
    {
        $booking = Booking::factory()->submitted()->create();

        $this->get(route('bookings.show', $booking))->assertNotFound();
        $this->get(route('bookings.show', ['booking' => $booking, 'token' => 'wrong']))->assertNotFound();
    }

    public function test_access_token_link_grants_access_for_the_session(): void
    {
        $booking = Booking::factory()->submitted(accessToken: 'secret-token')->create();

        $this->get(route('bookings.show', ['booking' => $booking, 'token' => 'secret-token']))
            ->assertRedirect(route('bookings.show', $booking));

        $this->get(route('bookings.show', $booking))->assertOk()->assertSee($booking->reference);
    }

    public function test_signed_email_link_grants_access(): void
    {
        $booking = Booking::factory()->submitted()->create();

        $this->get(URL::temporarySignedRoute('bookings.show', now()->addDay(), ['booking' => $booking->reference]))
            ->assertRedirect(route('bookings.show', $booking));
    }

    public function test_owner_can_view_but_other_customers_cannot(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('bookings.show', $booking))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('bookings.show', $booking))->assertNotFound();
    }

    public function test_numeric_ids_do_not_resolve_bookings(): void
    {
        $booking = Booking::factory()->submitted()->create();

        $this->actingAs(User::factory()->admin()->create())->get('/bookings/'.$booking->id)->assertNotFound();
    }

    public function test_customer_can_cancel_an_unpaid_booking(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted(BookingStatus::PendingQuote)->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('bookings.cancel', $booking), ['reason' => 'Plans changed'])
            ->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame('Plans changed', $booking->fresh()->cancellation_reason);
    }

    public function test_paid_booking_cancellation_becomes_a_request_inside_the_window(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create(['user_id' => $owner->id, 'preferred_date' => now()->addDays(10)]);

        $this->actingAs($owner)->post(route('bookings.cancel', $booking))->assertRedirect();

        $this->assertSame(BookingStatus::CancellationRequested, $booking->fresh()->status);
        Notification::assertSentTo(User::query()->admins()->first(), CancellationRequestedNotification::class);
    }

    public function test_paid_booking_cannot_be_cancelled_online_after_the_cutoff(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create(['user_id' => $owner->id, 'preferred_date' => now()->addDay()]);

        $this->actingAs($owner)->from(route('bookings.show', $booking))->post(route('bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_admin_can_view_but_cannot_act_for_the_customer(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('bookings.show', $booking))->assertOk();
        $this->actingAs($admin)->post(route('bookings.quote.accept', $booking))->assertNotFound();
    }

    public function test_printable_summary_renders_for_authorized_visitors(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('bookings.print', $booking))->assertOk()->assertSee($booking->reference);
    }

    public function test_printable_summary_is_hidden_from_guests(): void
    {
        $booking = Booking::factory()->submitted()->create(['user_id' => User::factory()->create()->id]);

        $this->get(route('bookings.print', $booking))->assertNotFound();
    }
}
