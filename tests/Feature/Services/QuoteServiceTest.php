<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Models\Payment;
use App\Models\TaxRate;
use App\Models\User;
use App\Notifications\Admin\QuoteAcceptedNotification;
use App\Notifications\Admin\QuoteExpiredNotification as AdminQuoteExpiredNotification;
use App\Notifications\Admin\QuoteSentCopyNotification;
use App\Notifications\Admin\RequoteRequestedNotification;
use App\Notifications\Customer\QuoteExpiredNotification;
use App\Notifications\Customer\QuoteSentNotification;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_quote_adds_tax_moves_booking_and_emails_the_customer(): void
    {
        Notification::fake();
        TaxRate::factory()->create(['rate_bps' => 500]);
        $booking = Booking::factory()->submitted(BookingStatus::PendingQuote)->create();

        $quote = app(QuoteService::class)->send($booking, 24000, 'Deep clean, 2 bed', now()->addDays(7), User::factory()->admin()->create());

        $this->assertSame(1200, $quote->tax_cents);
        $this->assertSame(25200, $quote->total_cents);
        $this->assertSame(QuoteStatus::Sent, $quote->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
        Notification::assertSentOnDemand(QuoteSentNotification::class);
        Notification::assertSentOnDemand(QuoteSentCopyNotification::class);
    }

    public function test_accepting_a_quote_notifies_the_admin_once(): void
    {
        Notification::fake();
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        BookingQuote::factory()->for($booking)->create();

        app(QuoteService::class)->accept($booking);
        app(QuoteService::class)->accept($booking->fresh());

        Notification::assertSentOnDemandTimes(QuoteAcceptedNotification::class, 1);
    }

    public function test_expiry_notifies_the_customer_and_admin_for_each_expired_quote(): void
    {
        Notification::fake();
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        BookingQuote::factory()->for($booking)->create(['expires_at' => now()->subHour()]);

        $this->artisan('quotes:expire')->assertSuccessful();

        Notification::assertSentOnDemand(QuoteExpiredNotification::class);
        Notification::assertSentOnDemand(AdminQuoteExpiredNotification::class);
    }

    public function test_requesting_a_new_quote_notifies_the_admin(): void
    {
        Notification::fake();
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        BookingQuote::factory()->for($booking)->create(['expires_at' => now()->subHour()]);

        app(QuoteService::class)->requestRequote($booking);

        Notification::assertSentOnDemand(RequoteRequestedNotification::class);
    }

    public function test_a_revised_quote_supersedes_the_previous_one(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->submitted(BookingStatus::PendingQuote)->create();
        $service = app(QuoteService::class);

        $first = $service->send($booking, 10000, 'First', now()->addDays(3), $admin);
        $second = $service->send($booking->fresh(), 12000, 'Revised', now()->addDays(3), $admin);

        $this->assertSame(QuoteStatus::Superseded, $first->fresh()->status);
        $this->assertSame($second->id, $booking->fresh()->currentQuote->id);
    }

    public function test_quotes_cannot_be_sent_for_confirmed_bookings(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();

        $this->expectException(BookingActionException::class);

        app(QuoteService::class)->send($booking, 10000, 'Nope', now()->addDays(3), User::factory()->admin()->create());
    }

    public function test_expired_quotes_cannot_be_accepted(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        BookingQuote::factory()->for($booking)->create(['expires_at' => now()->subMinute()]);

        $this->expectException(BookingActionException::class);

        app(QuoteService::class)->accept($booking);
    }

    public function test_expiry_returns_bookings_to_pending_quote_but_skips_open_payments(): void
    {
        $expiring = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        $expiringQuote = BookingQuote::factory()->for($expiring)->create(['expires_at' => now()->subHour()]);

        $paying = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        $payingQuote = BookingQuote::factory()->for($paying)->create(['expires_at' => now()->subHour(), 'status' => QuoteStatus::Accepted]);
        Payment::factory()->for($paying)->create(['booking_quote_id' => $payingQuote->id, 'status' => PaymentStatus::Processing]);

        $this->artisan('quotes:expire')->assertSuccessful();

        $this->assertSame(QuoteStatus::Expired, $expiringQuote->fresh()->status);
        $this->assertSame(BookingStatus::PendingQuote, $expiring->fresh()->status);
        $this->assertSame(QuoteStatus::Accepted, $payingQuote->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $paying->fresh()->status);
    }
}
