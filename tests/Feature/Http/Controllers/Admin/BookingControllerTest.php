<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\MessageType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Admin\BookingCancelledNotification as AdminBookingCancelledNotification;
use App\Notifications\Customer\BookingCancelledNotification;
use App\Notifications\Customer\BookingCompletedNotification;
use App\Notifications\Customer\CancellationDeclinedNotification;
use App\Notifications\Customer\QuoteSentNotification;
use App\Notifications\Customer\VisitScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        Notification::fake();
    }

    public function test_bookings_can_be_searched_and_filtered(): void
    {
        $match = Booking::factory()->submitted()->create(['contact_name' => 'Priya Findme']);
        Booking::factory()->submitted(BookingStatus::Confirmed)->create(['contact_name' => 'Other Person']);
        $draft = Booking::factory()->create(['contact_name' => 'Priya Draft']);

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', ['q' => 'Priya', 'status' => 'pending_quote']))
            ->assertOk()
            ->assertSee($match->reference)
            ->assertDontSee('Other Person')
            ->assertDontSee($draft->reference);
    }

    public function test_admin_views_the_full_conversation_including_internal_notes(): void
    {
        $booking = Booking::factory()->submitted()->create();
        $booking->messages()->create(['sender' => 'customer', 'type' => MessageType::Text, 'body' => 'Please clean the oven']);

        $this->actingAs($this->admin)->post(route('admin.bookings.notes.store', $booking), ['note' => 'Customer has a cat'])->assertRedirect();

        $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Please clean the oven')
            ->assertSee('Customer has a cat');
    }

    public function test_internal_notes_are_hidden_from_the_customer(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->submitted()->create(['user_id' => $owner->id]);
        $booking->messages()->create(['sender' => 'admin', 'type' => MessageType::InternalNote, 'body' => 'Secret staff note']);

        $this->actingAs($owner)->get(route('bookings.show', $booking))->assertOk()->assertDontSee('Secret staff note');
    }

    public function test_admin_sends_a_quote_from_the_booking_page(): void
    {
        $booking = Booking::factory()->submitted()->create();

        $this->actingAs($this->admin)->post(route('admin.bookings.quotes.store', $booking), [
            'amount' => '240.00',
            'description' => 'Deep clean of a two bedroom condo',
            'expires_at' => now()->addDays(7)->toDateString(),
        ])->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
        $this->assertSame(24000, $booking->fresh()->currentQuote->subtotal_cents);
        Notification::assertSentOnDemand(QuoteSentNotification::class);
    }

    public function test_admin_cannot_force_an_unpaid_booking_to_confirmed(): void
    {
        $booking = Booking::factory()->submitted()->create();

        $this->actingAs($this->admin)->from(route('admin.bookings.show', $booking))
            ->post(route('admin.bookings.transition', $booking), ['status' => 'confirmed'])
            ->assertSessionHasErrors('booking');

        $this->assertSame(BookingStatus::PendingQuote, $booking->fresh()->status);
    }

    public function test_admin_cancels_a_paid_booking_with_a_partial_refund(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::CancellationRequested)->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['amount_cents' => 20000, 'success_guard' => $booking->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.transition', $booking), [
            'status' => 'cancelled',
            'reason' => 'Approved request',
            'refund' => '150.00',
        ])->assertRedirect();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(15000, $payment->fresh()->refunded_cents);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        Notification::assertSentOnDemand(BookingCancelledNotification::class);
        Notification::assertSentOnDemand(AdminBookingCancelledNotification::class);
    }

    public function test_admin_moves_a_confirmed_booking_through_the_job_lifecycle(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();

        $this->actingAs($this->admin)->post(route('admin.bookings.transition', $booking), ['status' => 'in_progress']);
        $this->actingAs($this->admin)->post(route('admin.bookings.transition', $booking), ['status' => 'completed']);

        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
        $this->assertSame(2, $booking->statusHistory()->count());
        Notification::assertSentOnDemand(BookingCompletedNotification::class);
    }

    public function test_declining_a_cancellation_request_notifies_the_customer(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::CancellationRequested)->create();

        $this->actingAs($this->admin)->post(route('admin.bookings.transition', $booking), ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        Notification::assertSentOnDemand(CancellationDeclinedNotification::class);
    }

    public function test_admin_updates_booking_details_and_confirmed_arrival_time(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), [
            'contact_name' => 'New Name', 'contact_email' => 'new@example.com', 'contact_phone' => '604-555-0199',
            'preferred_date' => now()->addDays(4)->toDateString(), 'time_window' => 'evening',
            'scheduled_at' => now()->addDays(4)->format('Y-m-d').'T17:30',
            'line1' => '1 New St', 'city' => 'Burnaby', 'province' => 'BC', 'postal_code' => 'V5H 2E2',
        ])->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('New Name', $booking->contact_name);
        $this->assertSame('17:30', $booking->scheduled_at->format('H:i'));
        $this->assertSame('Burnaby', $booking->address->city);
        Notification::assertSentOnDemand(VisitScheduledNotification::class);
    }

    public function test_updating_booking_details_without_changing_the_arrival_time_does_not_notify_again(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create([
            'scheduled_at' => now()->addDays(4)->setTime(17, 30),
        ]);

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), [
            'contact_name' => 'Same Name', 'contact_email' => $booking->contact_email, 'contact_phone' => $booking->contact_phone,
            'preferred_date' => $booking->preferred_date->toDateString(), 'time_window' => $booking->time_window->value,
            'scheduled_at' => $booking->scheduled_at->format('Y-m-d\TH:i'),
            'line1' => $booking->address->line1, 'city' => $booking->address->city,
            'province' => $booking->address->province, 'postal_code' => $booking->address->postal_code,
        ])->assertRedirect(route('admin.bookings.show', $booking));

        Notification::assertNothingSent();
    }

    public function test_repeat_booking_creates_a_new_pending_quote_booking(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Completed)->create();

        $this->actingAs($this->admin)->post(route('admin.bookings.repeat', $booking), [
            'preferred_date' => now()->addDays(14)->toDateString(),
            'time_window' => 'morning',
        ])->assertRedirect();

        $copy = Booking::query()->where('repeated_from_id', $booking->id)->sole();
        $this->assertSame(BookingStatus::PendingQuote, $copy->status);
        $this->assertNotSame($booking->reference, $copy->reference);
        $this->assertSame($booking->address->line1, $copy->address->line1);
    }
}
