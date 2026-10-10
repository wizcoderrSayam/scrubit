<?php

namespace App\Services\Payments;

/**
 * A verified, provider-neutral payment event.
 */
final readonly class GatewayEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $provider,
        public string $eventId,
        public GatewayEventType $type,
        public string $providerRef,
        public ?int $amountCents,
        public ?string $failureReason,
        public array $payload,
        public ?string $currency = null,
    ) {}
}
