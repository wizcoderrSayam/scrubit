<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Contract for hosted-checkout payment providers. Implementations must never handle raw card data.
 */
interface PaymentGateway
{
    /**
     * The provider name stored on payments (e.g. "demo", "stripe").
     */
    public function name(): string;

    public function createCheckout(Payment $payment, string $returnUrl, string $cancelUrl): CheckoutSession;

    /**
     * Verify the webhook signature and translate the payload.
     *
     * @throws InvalidWebhookSignature
     */
    public function parseWebhook(Request $request): GatewayEvent;

    /**
     * Refund part or all of a captured payment.
     */
    public function refund(Payment $payment, int $amountCents): void;
}
