<?php

namespace App\Services\Pricing;

/**
 * Immutable server-side price calculation result. All amounts are integer cents.
 */
final readonly class PriceBreakdown
{
    /**
     * @param  list<array{label: string, amount_cents: int|null, needs_quote: bool}>  $lines
     * @param  list<array{name: string, rate_bps: int, amount_cents: int}>  $taxes
     * @param  list<string>  $quoteReasons
     */
    public function __construct(
        public array $lines,
        public int $subtotalCents,
        public int $discountCents,
        public array $taxes,
        public int $taxCents,
        public int $totalCents,
        public bool $quoteRequired,
        public array $quoteReasons,
        public string $currency,
    ) {}
}
