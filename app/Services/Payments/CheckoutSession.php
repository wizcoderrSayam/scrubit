<?php

namespace App\Services\Payments;

use Carbon\CarbonInterface;

final readonly class CheckoutSession
{
    public function __construct(
        public string $providerRef,
        public string $url,
        public CarbonInterface $expiresAt,
    ) {}
}
