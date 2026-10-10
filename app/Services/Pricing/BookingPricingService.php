<?php

namespace App\Services\Pricing;

use App\Enums\AddonPricingMethod;
use App\Enums\ServicePricingMethod;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\Service;
use App\Models\TaxRate;
use App\Services\SettingsService;
use App\Support\Money;

/**
 * Calculates every price from database values only. Request input never carries amounts.
 */
class BookingPricingService
{
    public function __construct(private SettingsService $settings) {}

    public function calculate(Booking $booking): PriceBreakdown
    {
        $booking->loadMissing(['service', 'addons']);
        $service = $booking->service;
        $currency = (string) $this->settings->get('business.currency', 'CAD');

        $lines = [];
        $reasons = [];
        $subtotal = 0;

        $baseCents = $this->serviceBaseCents($service);

        if ($baseCents === null) {
            $reasons[] = $service->pricing_method === ServicePricingMethod::StartingQuote
                ? "{$service->name} has a starting price; the final price is confirmed in a quote."
                : "{$service->name} is priced by custom quote.";
        } else {
            $subtotal += $baseCents;
        }

        $lines[] = ['label' => $service->name, 'amount_cents' => $baseCents, 'needs_quote' => $baseCents === null];

        foreach ($booking->addons as $addon) {
            $amount = $this->addonLineCents($addon);

            if ($amount === null) {
                $reasons[] = "\"{$addon->name}\" will be reviewed and quoted by our team.";
            } else {
                $subtotal += $amount;
            }

            $lines[] = ['label' => $addon->name, 'amount_cents' => $amount, 'needs_quote' => $amount === null];
        }

        $quoteRequired = $reasons !== [];

        if ($quoteRequired) {
            return new PriceBreakdown($lines, $subtotal, 0, [], 0, 0, true, $reasons, $currency);
        }

        $taxes = $this->taxesFor($subtotal);
        $taxCents = array_sum(array_column($taxes, 'amount_cents'));

        return new PriceBreakdown($lines, $subtotal, 0, $taxes, $taxCents, $subtotal + $taxCents, false, [], $currency);
    }

    /**
     * Apply each active tax rate once to the subtotal, rounding half up.
     *
     * @return list<array{name: string, rate_bps: int, amount_cents: int}>
     */
    public function taxesFor(int $subtotalCents): array
    {
        return TaxRate::query()->active()->get()
            ->map(fn (TaxRate $rate): array => [
                'name' => $rate->name.' ('.$rate->percentLabel().')',
                'rate_bps' => $rate->rate_bps,
                'amount_cents' => intdiv($subtotalCents * $rate->rate_bps + 5000, 10000),
            ])
            ->values()
            ->all();
    }

    /**
     * The base service price in cents, or null when it must be quoted.
     */
    public function serviceBaseCents(Service $service): ?int
    {
        return match ($service->pricing_method) {
            ServicePricingMethod::Fixed => $service->price_cents,
            ServicePricingMethod::Hourly => ($service->hourly_rate_cents !== null && $service->estimated_minutes)
                ? intdiv($service->hourly_rate_cents * $service->estimated_minutes + 30, 60)
                : null,
            ServicePricingMethod::StartingQuote, ServicePricingMethod::CustomQuote => null,
        };
    }

    public function addonLineCents(BookingAddon $addon): ?int
    {
        if ($addon->needs_quote || $addon->unit_price_cents === null || $addon->pricing_method === AddonPricingMethod::Quote) {
            return null;
        }

        return $addon->unit_price_cents * max(1, $addon->quantity);
    }

    public function describeTerms(Service $service, string $currency): string
    {
        return match ($service->pricing_method) {
            ServicePricingMethod::Fixed => 'Fixed price of '.Money::format((int) $service->price_cents, $currency),
            ServicePricingMethod::Hourly => 'Hourly rate of '.Money::format((int) $service->hourly_rate_cents, $currency).' for an estimated '.$service->estimated_minutes.' minutes',
            ServicePricingMethod::StartingQuote => 'Starting price'.($service->price_cents !== null ? ' of '.Money::format($service->price_cents, $currency) : '').'; final price by quote',
            ServicePricingMethod::CustomQuote => 'Priced by custom quote',
        };
    }
}
