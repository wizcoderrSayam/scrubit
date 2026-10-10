<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\InvalidWebhookSignature;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\UnknownPaymentException;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(string $provider, Request $request, PaymentGatewayManager $gateways, WebhookProcessor $processor): JsonResponse
    {
        abort_unless($gateways->isConfigured($provider), 404);

        try {
            $event = $gateways->driver($provider)->parseWebhook($request);
        } catch (InvalidWebhookSignature $exception) {
            Log::warning('Rejected payment webhook', ['provider' => $provider, 'reason' => $exception->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            $applied = $processor->process($event);
        } catch (UnknownPaymentException) {
            return response()->json(['error' => 'Unknown payment'], 409);
        }

        return response()->json(['received' => true, 'duplicate' => ! $applied]);
    }
}
