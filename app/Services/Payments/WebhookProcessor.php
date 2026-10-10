<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records each verified event once and applies it. Duplicate deliveries are acknowledged and ignored.
 */
class WebhookProcessor
{
    public function __construct(private PaymentService $payments) {}

    /**
     * @return bool True when the event was applied, false when it was a duplicate.
     */
    public function process(GatewayEvent $event): bool
    {
        try {
            DB::transaction(function () use ($event): void {
                PaymentEvent::query()->create([
                    'provider' => $event->provider,
                    'event_id' => $event->eventId,
                    'type' => $event->type->value,
                    'payment_id' => Payment::query()
                        ->where('provider', $event->provider)
                        ->where('provider_ref', $event->providerRef)
                        ->value('id'),
                    'payload' => $event->payload,
                    'processed_at' => now(),
                ]);

                $this->payments->handleEvent($event);
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
