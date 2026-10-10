<?php

namespace Tests\Feature\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Services\Payments\PaymentService;
use App\Services\Payments\StripeCheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class StripeCheckoutGatewayTest extends TestCase
{
    use BuildsBookings, RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.driver' => 'stripe',
            'payments.stripe.secret' => 'sk_test_123',
            'payments.stripe.webhook_secret' => self::WEBHOOK_SECRET,
            'payments.stripe.api_base' => 'https://api.stripe.test/v1',
        ]);

        Http::preventStrayRequests();
        Notification::fake();
    }

    private function fakeSessionCreation(string $sessionId = 'cs_test_1'): void
    {
        Http::fake([
            'api.stripe.test/v1/checkout/sessions' => Http::response([
                'id' => $sessionId,
                'url' => 'https://checkout.stripe.test/c/pay/'.$sessionId,
                'expires_at' => now()->addMinutes(30)->getTimestamp(),
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function sendStripeEvent(string $type, array $session, string $eventId = 'evt_1', ?string $secret = null): TestResponse
    {
        $body = json_encode(['id' => $eventId, 'type' => $type, 'data' => ['object' => $session]]);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret ?? self::WEBHOOK_SECRET);

        return $this->call('POST', route('webhooks.payments', 'stripe'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], content: $body);
    }

    private function startStripeCheckout(int $total = 21000): array
    {
        $this->fakeSessionCreation();
        [$booking] = $this->bookingWithAcceptedQuote($total);

        return [$booking, app(PaymentService::class)->startCheckout($booking)];
    }

    public function test_creates_a_hosted_checkout_session_for_the_server_side_amount(): void
    {
        [$booking, $payment] = $this->startStripeCheckout(31500);

        $this->assertSame('stripe', $payment->provider);
        $this->assertSame('cs_test_1', $payment->provider_ref);
        $this->assertSame('https://checkout.stripe.test/c/pay/cs_test_1', $payment->checkout_url);

        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.stripe.test/v1/checkout/sessions'
            && $request->hasHeader('Authorization', 'Bearer sk_test_123')
            && $request->hasHeader('Idempotency-Key', 'checkout-'.$payment->idempotency_key)
            && $request['line_items'][0]['price_data']['unit_amount'] == 31500
            && $request['line_items'][0]['price_data']['currency'] === 'cad'
            && $request['mode'] === 'payment'
            && $request['metadata']['booking'] === $booking->reference);
    }

    public function test_completed_session_confirms_the_booking_once(): void
    {
        [$booking, $payment] = $this->startStripeCheckout();
        $session = ['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 21000, 'currency' => 'cad', 'payment_intent' => 'pi_1'];

        $this->sendStripeEvent('checkout.session.completed', $session)->assertOk()->assertJsonPath('duplicate', false);
        $this->sendStripeEvent('checkout.session.completed', $session)->assertOk()->assertJsonPath('duplicate', true);

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseCount(PaymentEvent::class, 1);
    }

    public function test_async_payments_wait_for_the_success_event(): void
    {
        [$booking, $payment] = $this->startStripeCheckout();

        $this->sendStripeEvent('checkout.session.completed', ['id' => 'cs_test_1', 'payment_status' => 'unpaid', 'amount_total' => 21000], 'evt_a')->assertOk();
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);

        $this->sendStripeEvent('checkout.session.async_payment_succeeded', ['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 21000], 'evt_b')->assertOk();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_failed_and_expired_sessions_leave_the_booking_payable(): void
    {
        [$booking, $payment] = $this->startStripeCheckout();

        $this->sendStripeEvent('checkout.session.expired', ['id' => 'cs_test_1', 'payment_status' => 'unpaid'])->assertOk();

        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_rejects_events_signed_with_the_wrong_secret(): void
    {
        [$booking, $payment] = $this->startStripeCheckout();

        $this->sendStripeEvent('checkout.session.completed', ['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 21000], secret: 'whsec_wrong')
            ->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_acknowledges_but_ignores_unrelated_event_types(): void
    {
        $this->sendStripeEvent('customer.created', ['id' => 'cus_1'])->assertOk()->assertJsonPath('ignored', true);

        $this->assertDatabaseCount(PaymentEvent::class, 0);
    }

    public function test_refund_uses_the_payment_intent_with_an_idempotency_key(): void
    {
        Http::fake([
            'api.stripe.test/v1/checkout/sessions/cs_paid' => Http::response(['id' => 'cs_paid', 'payment_intent' => 'pi_paid']),
            'api.stripe.test/v1/refunds' => Http::response(['id' => 're_1', 'status' => 'succeeded']),
        ]);
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['provider' => 'stripe', 'provider_ref' => 'cs_paid', 'amount_cents' => 20000]);

        app(PaymentService::class)->refund($payment, 5000, User::factory()->admin()->create());

        $this->assertSame(5000, $payment->fresh()->refunded_cents);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.stripe.test/v1/refunds'
            && $request['payment_intent'] === 'pi_paid'
            && $request['amount'] == 5000
            && $request->hasHeader('Idempotency-Key', "refund-{$payment->idempotency_key}-0-5000"));
    }

    public function test_a_failed_refund_call_records_nothing(): void
    {
        Http::fake([
            'api.stripe.test/v1/checkout/sessions/cs_paid' => Http::response(['id' => 'cs_paid', 'payment_intent' => 'pi_paid']),
            'api.stripe.test/v1/refunds' => Http::response(['error' => ['message' => 'declined']], 402),
        ]);
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['provider' => 'stripe', 'provider_ref' => 'cs_paid', 'amount_cents' => 20000]);

        try {
            app(PaymentService::class)->refund($payment, 5000, User::factory()->admin()->create());
            $this->fail('Expected the refund to fail.');
        } catch (RequestException) {
            // expected
        }

        $this->assertSame(0, $payment->fresh()->refunded_cents);
        $this->assertArrayNotHasKey('refund_in_progress', (array) $payment->fresh()->meta);
    }

    public function test_session_expiry_is_kept_inside_stripes_allowed_window(): void
    {
        config(['payments.checkout_ttl_minutes' => 5]);
        $this->startStripeCheckout();

        Http::assertSent(fn (HttpRequest $request) => $request['expires_at'] >= now()->addMinutes(31)->getTimestamp() - 2);
    }

    public function test_session_expiry_never_exceeds_twenty_four_hours(): void
    {
        config(['payments.checkout_ttl_minutes' => 5000]);
        $this->startStripeCheckout();

        Http::assertSent(fn (HttpRequest $request) => $request['expires_at'] <= now()->addDay()->getTimestamp());
    }

    public function test_a_lost_checkout_response_is_recovered_with_the_same_idempotency_key(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        Http::fakeSequence('api.stripe.test/v1/checkout/sessions')
            ->push(['error' => ['message' => 'timeout']], 500)
            ->push(['error' => ['message' => 'timeout']], 500)
            ->push(['error' => ['message' => 'timeout']], 500)
            ->push(['id' => 'cs_recovered', 'url' => 'https://checkout.stripe.test/cs_recovered', 'expires_at' => now()->addMinutes(31)->getTimestamp()]);

        try {
            app(PaymentService::class)->startCheckout($booking);
            $this->fail('Expected the first checkout attempt to fail.');
        } catch (BookingActionException) {
            // expected
        }

        $pending = Payment::query()->sole();
        $this->assertSame(PaymentStatus::Pending, $pending->status);

        $retry = app(PaymentService::class)->startCheckout($booking->fresh());

        $this->assertSame($pending->id, $retry->id);
        $this->assertSame('cs_recovered', $retry->provider_ref);
        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Idempotency-Key', 'checkout-'.$pending->idempotency_key));
        Http::assertSentCount(4);
    }

    public function test_client_errors_are_not_retried(): void
    {
        [$booking] = $this->bookingWithAcceptedQuote();
        Http::fake(['api.stripe.test/v1/checkout/sessions' => Http::response(['error' => ['message' => 'invalid']], 400)]);

        try {
            app(PaymentService::class)->startCheckout($booking);
        } catch (BookingActionException) {
            // expected
        }

        Http::assertSentCount(1);
    }

    public function test_currency_mismatch_is_flagged_instead_of_confirming(): void
    {
        [$booking, $payment] = $this->startStripeCheckout();

        $this->sendStripeEvent('checkout.session.completed', ['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 21000, 'currency' => 'usd'])->assertOk();

        $this->assertTrue($payment->fresh()->needs_attention);
        $this->assertSame(BookingStatus::AwaitingQuotePayment, $booking->fresh()->status);
    }

    public function test_no_payment_required_sessions_count_as_paid(): void
    {
        [$booking] = $this->startStripeCheckout();

        $this->sendStripeEvent('checkout.session.completed', ['id' => 'cs_test_1', 'payment_status' => 'no_payment_required', 'amount_total' => 21000, 'currency' => 'cad'])->assertOk();

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_accepts_any_matching_v1_signature_and_rejects_stale_timestamps(): void
    {
        [$booking] = $this->startStripeCheckout();
        $body = json_encode(['id' => 'evt_multi', 'type' => 'checkout.session.completed', 'data' => ['object' => ['id' => 'cs_test_1', 'payment_status' => 'paid', 'amount_total' => 21000, 'currency' => 'cad']]]);
        $sign = fn (int $timestamp) => hash_hmac('sha256', $timestamp.'.'.$body, self::WEBHOOK_SECRET);
        $post = fn (string $header) => $this->call('POST', route('webhooks.payments', 'stripe'), server: ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $header], content: $body);

        $stale = time() - 3600;
        $post("t={$stale},v1={$sign($stale)}")->assertStatus(400);

        $now = time();
        $post("t={$now},v1=oldrolledsecret,v1={$sign($now)}")->assertOk();
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_requires_keys_to_be_configured(): void
    {
        config(['payments.stripe.secret' => null]);

        $this->expectException(RuntimeException::class);

        new StripeCheckoutGateway;
    }
}
