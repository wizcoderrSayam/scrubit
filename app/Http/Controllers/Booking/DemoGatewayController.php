<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BookingAccess;
use App\Services\Payments\DemoGateway;
use App\Services\Payments\GatewayEventType;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The simulated hosted checkout page used by the demo payment driver.
 */
class DemoGatewayController extends Controller
{
    public function __construct(
        private BookingAccess $access,
        private PaymentGatewayManager $gateways,
    ) {}

    public function show(Payment $payment, Request $request): View|RedirectResponse
    {
        $this->authorizePayment($payment, $request);

        if (! $payment->status->isOpen() || $this->isExpired($payment)) {
            return to_route('checkout.return', ['booking' => $payment->booking, 'payment' => $payment->idempotency_key]);
        }

        return view('checkout.demo-gateway', ['payment' => $payment->load('booking')]);
    }

    public function complete(Payment $payment, Request $request, WebhookProcessor $processor): RedirectResponse
    {
        $this->authorizePayment($payment, $request);

        $outcome = $request->validate(['outcome' => ['required', Rule::in(['succeed', 'fail', 'cancel', 'processing'])]])['outcome'];
        $booking = $payment->booking;

        if ($payment->status->isOpen() && ! $this->isExpired($payment)) {
            /** @var DemoGateway $gateway */
            $gateway = $this->gateways->driver('demo');
            $type = match ($outcome) {
                'succeed' => GatewayEventType::Succeeded,
                'fail' => GatewayEventType::Failed,
                'cancel' => GatewayEventType::Cancelled,
                'processing' => GatewayEventType::Processing,
            };

            $signed = $gateway->simulate($payment, $type);
            $webhookRequest = Request::create(route('webhooks.payments', 'demo'), 'POST', server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_'.str_replace('-', '_', strtoupper(DemoGateway::SIGNATURE_HEADER)) => $signed['signature'],
            ], content: $signed['body']);

            $processor->process($gateway->parseWebhook($webhookRequest));
        }

        $route = $outcome === 'cancel' ? 'checkout.cancel' : 'checkout.return';

        return to_route($route, ['booking' => $booking, 'payment' => $payment->idempotency_key]);
    }

    private function isExpired(Payment $payment): bool
    {
        return $payment->checkout_expires_at !== null && $payment->checkout_expires_at->isPast();
    }

    private function authorizePayment(Payment $payment, Request $request): void
    {
        abort_unless(config('payments.driver') === 'demo' && $payment->provider === 'demo', 404);
        abort_unless($this->access->canAct($payment->booking, $request), 404);
    }
}
