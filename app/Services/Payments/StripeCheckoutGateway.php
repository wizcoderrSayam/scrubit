<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Stripe Checkout over Stripe's REST API (no SDK dependency).
 *
 * Customers pay on Stripe's hosted page, so card data never reaches this application.
 * Payments are confirmed only from signed webhook events.
 */
class StripeCheckoutGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'Stripe-Signature';

    /**
     * Stripe accepts 30 minutes to 24 hours; one extra minute absorbs clock skew and latency.
     */
    private const MIN_SESSION_MINUTES = 31;

    private const MAX_SESSION_MINUTES = 1440;

    public function __construct()
    {
        if (blank(config('payments.stripe.secret')) || blank(config('payments.stripe.webhook_secret'))) {
            throw new RuntimeException('Stripe is not configured. Set STRIPE_SECRET and STRIPE_WEBHOOK_SECRET.');
        }
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckout(Payment $payment, string $returnUrl, string $cancelUrl): CheckoutSession
    {
        $payment->loadMissing('booking');
        $minutes = min(self::MAX_SESSION_MINUTES, max(self::MIN_SESSION_MINUTES, (int) config('payments.checkout_ttl_minutes', 30)));

        $response = $this->client()
            ->withHeaders(['Idempotency-Key' => 'checkout-'.$payment->idempotency_key])
            ->post('/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $payment->idempotency_key,
                'customer_email' => $payment->booking->contact_email,
                'expires_at' => now()->addMinutes($minutes)->getTimestamp(),
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'unit_amount' => $payment->amount_cents,
                        'product_data' => [
                            'name' => $payment->booking->service_name.' ('.$payment->booking->reference.')',
                        ],
                    ],
                ]],
                'metadata' => ['payment_key' => $payment->idempotency_key, 'booking' => $payment->booking->reference],
                'payment_intent_data' => [
                    'metadata' => ['payment_key' => $payment->idempotency_key, 'booking' => $payment->booking->reference],
                ],
            ])
            ->throw();

        return new CheckoutSession(
            providerRef: (string) $response->json('id'),
            url: (string) $response->json('url'),
            expiresAt: CarbonImmutable::createFromTimestamp((int) $response->json('expires_at')),
        );
    }

    public function parseWebhook(Request $request): GatewayEvent
    {
        $body = $request->getContent();
        $this->verifySignature($body, (string) $request->header(self::SIGNATURE_HEADER));

        $event = json_decode($body, true);

        if (! is_array($event) || ! is_string($event['id'] ?? null) || ! is_string($event['type'] ?? null)) {
            throw new InvalidWebhookSignature('Malformed Stripe webhook payload.');
        }

        $session = (array) data_get($event, 'data.object', []);

        $type = match ($event['type']) {
            'checkout.session.completed' => in_array($session['payment_status'] ?? null, ['paid', 'no_payment_required'], true)
                ? GatewayEventType::Succeeded
                : GatewayEventType::Processing,
            'checkout.session.async_payment_succeeded' => GatewayEventType::Succeeded,
            'checkout.session.async_payment_failed' => GatewayEventType::Failed,
            'checkout.session.expired' => GatewayEventType::Cancelled,
            default => throw new IgnoredWebhookEvent("Stripe event {$event['type']} is not handled."),
        };

        if (! is_string($session['id'] ?? null)) {
            throw new InvalidWebhookSignature('Stripe webhook is missing the checkout session id.');
        }

        return new GatewayEvent(
            provider: $this->name(),
            eventId: $event['id'],
            type: $type,
            providerRef: $session['id'],
            amountCents: isset($session['amount_total']) ? (int) $session['amount_total'] : null,
            failureReason: $type === GatewayEventType::Failed ? 'The payment could not be completed.' : null,
            payload: [
                'id' => $event['id'],
                'type' => $event['type'],
                'session' => $session['id'],
                'payment_intent' => $session['payment_intent'] ?? null,
                'payment_status' => $session['payment_status'] ?? null,
                'amount_total' => $session['amount_total'] ?? null,
                'currency' => $session['currency'] ?? null,
            ],
            currency: isset($session['currency']) ? (string) $session['currency'] : null,
        );
    }

    /**
     * Refund through the session's payment intent. The idempotency key is derived from the
     * amount already refunded, so a retried request can never refund twice.
     */
    public function refund(Payment $payment, int $amountCents): void
    {
        $paymentIntent = $this->client()
            ->get('/checkout/sessions/'.urlencode((string) $payment->provider_ref))
            ->throw()
            ->json('payment_intent');

        if (! is_string($paymentIntent) || $paymentIntent === '') {
            throw new RuntimeException('Stripe has no payment for this checkout session.');
        }

        $this->client()
            ->withHeaders(['Idempotency-Key' => "refund-{$payment->idempotency_key}-{$payment->refunded_cents}-{$amountCents}"])
            ->post('/refunds', [
                'payment_intent' => $paymentIntent,
                'amount' => $amountCents,
                'metadata' => ['payment_key' => $payment->idempotency_key],
            ])
            ->throw();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('payments.stripe.api_base'), '/'))
            ->withToken((string) config('payments.stripe.secret'))
            ->asForm()
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500, when: fn (\Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && ($exception->response->status() === 429 || $exception->response->serverError())), throw: false);
    }

    private function verifySignature(string $body, string $header): void
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, '');

            if ($key === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            throw new InvalidWebhookSignature('Missing Stripe signature.');
        }

        if (abs(time() - $timestamp) > (int) config('payments.stripe.tolerance_seconds', 300)) {
            throw new InvalidWebhookSignature('Stripe signature timestamp is outside the allowed window.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('payments.stripe.webhook_secret'));

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return;
            }
        }

        throw new InvalidWebhookSignature('Invalid Stripe signature.');
    }
}
