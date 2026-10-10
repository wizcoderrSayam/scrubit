<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Simulated hosted checkout for development and demos.
 *
 * The demo checkout page posts the chosen outcome as an HMAC-signed webhook that goes
 * through the same verification and processing code as a real provider's webhook.
 */
class DemoGateway implements PaymentGateway
{
    public const SIGNATURE_HEADER = 'X-Demo-Signature';

    public function __construct()
    {
        if (app()->isProduction() && ! config('payments.demo.allow_production')) {
            throw new RuntimeException('The demo payment gateway is disabled in production. Configure a real PAYMENT_DRIVER.');
        }
    }

    public function name(): string
    {
        return 'demo';
    }

    public function createCheckout(Payment $payment, string $returnUrl, string $cancelUrl): CheckoutSession
    {
        return new CheckoutSession(
            providerRef: 'demo_'.$payment->idempotency_key,
            url: route('demo-gateway.show', ['payment' => $payment->idempotency_key]),
            expiresAt: now()->addMinutes((int) config('payments.checkout_ttl_minutes', 30)),
        );
    }

    public function parseWebhook(Request $request): GatewayEvent
    {
        $body = $request->getContent();
        $this->verifySignature($body, (string) $request->header(self::SIGNATURE_HEADER));

        $payload = json_decode($body, true);
        $type = is_array($payload) ? GatewayEventType::tryFrom((string) ($payload['type'] ?? '')) : null;

        if (! is_array($payload) || $type === null || ! is_string($payload['id'] ?? null) || ! is_string($payload['payment_ref'] ?? null)) {
            throw new InvalidWebhookSignature('Malformed demo webhook payload.');
        }

        return new GatewayEvent(
            provider: $this->name(),
            eventId: $payload['id'],
            type: $type,
            providerRef: $payload['payment_ref'],
            amountCents: isset($payload['amount_cents']) ? (int) $payload['amount_cents'] : null,
            failureReason: isset($payload['failure_reason']) ? (string) $payload['failure_reason'] : null,
            payload: $payload,
            currency: isset($payload['currency']) ? (string) $payload['currency'] : null,
        );
    }

    public function refund(Payment $payment, int $amountCents): void
    {
        // The demo provider has no remote state; PaymentService records the refund.
    }

    /**
     * Build a signed webhook body for a simulated outcome.
     *
     * @return array{body: string, signature: string}
     */
    public function simulate(Payment $payment, GatewayEventType $type, ?int $timestamp = null): array
    {
        $body = json_encode([
            'id' => 'evt_'.Str::uuid(),
            'type' => $type->value,
            'payment_ref' => $payment->provider_ref,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'failure_reason' => $type === GatewayEventType::Failed ? 'The demo card was declined.' : null,
            'created' => $timestamp ?? time(),
        ], JSON_THROW_ON_ERROR);

        return ['body' => $body, 'signature' => $this->sign($body, $timestamp ?? time())];
    }

    public function sign(string $body, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $this->secret());
    }

    private function verifySignature(string $body, string $header): void
    {
        $parts = [];

        foreach (explode(',', $header) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = ctype_digit($parts['t'] ?? '') ? (int) $parts['t'] : null;
        $signature = $parts['v1'] ?? '';

        if ($timestamp === null || $signature === '') {
            throw new InvalidWebhookSignature('Missing demo webhook signature.');
        }

        if (abs(time() - $timestamp) > (int) config('payments.demo.tolerance_seconds', 300)) {
            throw new InvalidWebhookSignature('Demo webhook timestamp is outside the allowed window.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $this->secret());

        if (! hash_equals($expected, $signature)) {
            throw new InvalidWebhookSignature('Invalid demo webhook signature.');
        }
    }

    private function secret(): string
    {
        $secret = (string) config('payments.demo.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('DEMO_WEBHOOK_SECRET is not configured.');
        }

        return $secret;
    }
}
