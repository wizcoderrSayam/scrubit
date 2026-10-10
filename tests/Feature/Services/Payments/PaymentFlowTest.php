<?php

namespace Tests\Feature\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Notifications\Admin\PaymentNeedsAttentionNotification;
use App\Notifications\Customer\BookingConfirmedNotification;
use App\Services\Payments\DemoGateway;
use App\Services\Payments\GatewayEventType;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private function payments(): PaymentService
    {
        return app(PaymentService::class);
    }

    public function test_checkout_requires_an_accepted_quote(): void
    {
        [$booking, $quote] = $this->bookingWithAcceptedQuote();
        $quote->forceFill(['status' => QuoteStatus::Sent, 'accepted_at' => null])->save();

        $this->expectException(BookingActionException::class);

        $this->payments()->startCheckout($booking);
    }

    public function test_bookings_waiting_for_a_quote_cannot_be_charged(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::PendingQuote)->create(['total_cents' => 5000]);

        $this->expectException(BookingActionException::class);

        $this->payments()->startCheckout($booking);
    }

    public function test_checkout_creates_a_pending_payment_for_the_quote_total_and_reuses_it(): void
    {
        [$booking, $quote] = $this->bookingWithAcceptedQuote(31500);

        $payment = $this->payments()->startCheckout($booking);
        $again = $this->payments()->startCheckout($booking->fresh());

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(31500, $payment->amount_cents);
        $this->assertSame($quote->id, $payment->booking_quote_id);
        $this->assertStringStartsWith('demo_', $payment->provider_ref);
        $this->assertSame($payment->id, $again->id);
        $this->assertDatabaseCount(Payment::class, 1);
    }

    public function test_verified_success_webhook_confirms_booking_once_even_when_delivered_twice(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);

        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded, eventId: 'evt_same')->assertOk()->assertJsonPath('duplicate', false);
        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded, eventId: 'evt_same')->assertOk()->assertJsonPath('duplicate', true);
        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded, eventId: 'evt_other')->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertSame($booking->id, $payment->success_guard);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseCount(PaymentEvent::class, 2);
        $this->assertSame(1, $booking->statusHistory()->where('to_status', BookingStatus::Confirmed)->count());
        Notification::assertSentOnDemandTimes(BookingConfirmedNotification::class, 1);
    }

    public function test_invalid_signature_is_rejected_with_400(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $body = json_encode(['id' => 'evt_1', 'type' => 'payment.succeeded', 'payment_ref' => $payment->provider_ref]);

        $this->call('POST', route('webhooks.payments', 'demo'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_SIGNATURE' => 't='.time().',v1=forged',
        ], content: $body)->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_stale_signature_timestamp_is_rejected(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $body = json_encode(['id' => 'evt_old', 'type' => 'payment.succeeded', 'payment_ref' => $payment->provider_ref]);
        $oldTimestamp = time() - 3600;

        $this->call('POST', route('webhooks.payments', 'demo'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_SIGNATURE' => app(DemoGateway::class)->sign($body, $oldTimestamp),
        ], content: $body)->assertStatus(400);
    }

    public function test_returning_to_the_success_url_alone_does_not_mark_the_booking_paid(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);

        $this->withSession(['accessible_bookings' => [$booking->reference]])
            ->get(route('checkout.return', ['booking' => $booking, 'payment' => $payment->idempotency_key]))
            ->assertOk()
            ->assertSee('Confirming your payment');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_failed_payment_keeps_booking_payable_and_retry_creates_a_new_payment(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $first = $this->payments()->startCheckout($booking);

        $this->sendDemoWebhook($first, GatewayEventType::Failed, ['failure_reason' => 'Card declined'])->assertOk();

        $this->assertSame(PaymentStatus::Failed, $first->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);

        $retry = $this->payments()->startCheckout($booking->fresh());
        $this->assertNotSame($first->id, $retry->id);
    }

    public function test_processing_payment_blocks_a_second_checkout(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $this->sendDemoWebhook($payment, GatewayEventType::Processing)->assertOk();

        $this->expectException(BookingActionException::class);

        $this->payments()->startCheckout($booking->fresh());
    }

    public function test_late_success_for_a_cancelled_booking_is_flagged_and_does_not_confirm(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded)->assertOk();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertTrue($payment->needs_attention);
        $this->assertNull($payment->success_guard);
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        Notification::assertSentTo(User::query()->admins()->first(), PaymentNeedsAttentionNotification::class);
    }

    public function test_amount_mismatch_is_flagged_instead_of_confirming(): void
    {
        Notification::fake();
        [$booking] = $this->bookingWithAcceptedQuote(21000);
        $payment = $this->payments()->startCheckout($booking);

        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded, ['amount_cents' => 100])->assertOk();

        $this->assertTrue($payment->fresh()->needs_attention);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_demo_checkout_page_simulates_success_through_signed_webhook(): void
    {
        Notification::fake();
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $session = ['accessible_bookings' => [$booking->reference]];

        $this->withSession($session)->get(route('demo-gateway.show', $payment->idempotency_key))->assertOk()->assertSee('DEMO PAYMENT GATEWAY');
        $this->withSession($session)->post(route('demo-gateway.complete', $payment->idempotency_key), ['outcome' => 'succeed'])
            ->assertRedirect(route('checkout.return', ['booking' => $booking, 'payment' => $payment->idempotency_key]));

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseCount(PaymentEvent::class, 1);
    }

    public function test_success_after_the_quote_expired_is_flagged_instead_of_confirming(): void
    {
        Notification::fake();
        [$booking, $quote] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $quote->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->sendDemoWebhook($payment, GatewayEventType::Succeeded)->assertOk();

        $this->assertTrue($payment->fresh()->needs_attention);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_demo_processing_payment_can_still_be_completed(): void
    {
        Notification::fake();
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $session = ['accessible_bookings' => [$booking->reference]];

        $this->withSession($session)->post(route('demo-gateway.complete', $payment->idempotency_key), ['outcome' => 'processing']);
        $this->withSession($session)->get(route('demo-gateway.show', $payment->idempotency_key))->assertOk();
        $this->withSession($session)->post(route('demo-gateway.complete', $payment->idempotency_key), ['outcome' => 'succeed']);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_stuck_processing_payments_are_released_and_flagged(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $payment->forceFill(['status' => PaymentStatus::Processing])->save();
        Payment::query()->whereKey($payment->id)->update(['updated_at' => now()->subHours(25)]);

        $this->artisan('payments:expire-checkouts')->assertSuccessful();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->needs_attention);
        $this->assertFalse($booking->fresh()->hasOpenPayment());
    }

    public function test_events_for_unknown_payments_are_not_acknowledged_or_stored(): void
    {
        $orphan = Payment::factory()->make(['provider_ref' => 'demo_does-not-exist', 'amount_cents' => 100]);

        $this->sendDemoWebhook($orphan, GatewayEventType::Succeeded)->assertStatus(409);

        $this->assertDatabaseCount(PaymentEvent::class, 0);
    }

    public function test_expired_demo_checkout_cannot_be_completed(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);
        $payment->forceFill(['checkout_expires_at' => now()->subMinute()])->save();

        $this->withSession(['accessible_bookings' => [$booking->reference]])
            ->post(route('demo-gateway.complete', $payment->idempotency_key), ['outcome' => 'succeed']);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_demo_checkout_page_is_not_available_to_other_visitors(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        $payment = $this->payments()->startCheckout($booking);

        $this->get(route('demo-gateway.show', $payment->idempotency_key))->assertNotFound();
        $this->post(route('demo-gateway.complete', $payment->idempotency_key), ['outcome' => 'succeed'])->assertNotFound();
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_demo_gateway_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['payments.demo.allow_production' => false]);

        $this->expectException(RuntimeException::class);

        new DemoGateway;
    }

    public function test_refunds_cannot_exceed_the_paid_amount(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['amount_cents' => 10000]);
        $admin = User::factory()->admin()->create();

        $this->payments()->refund($payment, 4000, $admin);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);

        $this->expectException(BookingActionException::class);
        $this->payments()->refund($payment->fresh(), 6001, $admin);
    }
}
