<?php

namespace Tests\Feature\Services\Pricing;

use App\Enums\AddonPricingMethod;
use App\Enums\AddonSource;
use App\Enums\ServicePricingMethod;
use App\Models\Booking;
use App\Models\Service;
use App\Models\TaxRate;
use App\Services\Pricing\BookingPricingService;
use App\Services\Pricing\PriceBreakdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function priceFor(Service $service, array $addons = []): PriceBreakdown
    {
        $booking = Booking::factory()->create(['service_id' => $service->id]);

        foreach ($addons as $addon) {
            $booking->addons()->create([
                'name' => $addon['name'] ?? 'Extra',
                'pricing_method' => $addon['pricing_method'] ?? AddonPricingMethod::Fixed,
                'unit_price_cents' => $addon['unit_price_cents'] ?? null,
                'quantity' => $addon['quantity'] ?? 1,
                'needs_quote' => $addon['needs_quote'] ?? false,
                'source' => AddonSource::Selected,
            ]);
        }

        return app(BookingPricingService::class)->calculate($booking->fresh());
    }

    public function test_fixed_price_service_totals_with_tax(): void
    {
        TaxRate::factory()->create(['rate_bps' => 500]);

        $price = $this->priceFor(Service::factory()->fixed(15000)->create());

        $this->assertFalse($price->quoteRequired);
        $this->assertSame(15000, $price->subtotalCents);
        $this->assertSame(750, $price->taxCents);
        $this->assertSame(15750, $price->totalCents);
    }

    public function test_hourly_price_uses_estimated_minutes_with_half_up_rounding(): void
    {
        $price = $this->priceFor(Service::factory()->hourly(rateCents: 3333, estimatedMinutes: 90)->create());

        $this->assertSame(5000, $price->subtotalCents);
    }

    public function test_custom_quote_service_requires_quote_and_charges_nothing(): void
    {
        TaxRate::factory()->create();

        $price = $this->priceFor(Service::factory()->create(['pricing_method' => ServicePricingMethod::CustomQuote]));

        $this->assertTrue($price->quoteRequired);
        $this->assertSame(0, $price->totalCents);
        $this->assertNotEmpty($price->quoteReasons);
    }

    public function test_starting_price_service_requires_quote(): void
    {
        $price = $this->priceFor(Service::factory()->create(['pricing_method' => ServicePricingMethod::StartingQuote, 'price_cents' => 9900]));

        $this->assertTrue($price->quoteRequired);
        $this->assertSame(0, $price->totalCents);
    }

    public function test_priced_addons_are_added_and_quoted_addons_force_a_quote(): void
    {
        $service = Service::factory()->fixed(10000)->create();

        $priced = $this->priceFor($service, [['unit_price_cents' => 2500, 'quantity' => 2]]);
        $this->assertSame(15000, $priced->subtotalCents);
        $this->assertFalse($priced->quoteRequired);

        $quoted = $this->priceFor($service, [['name' => 'Inside oven', 'pricing_method' => AddonPricingMethod::Quote, 'needs_quote' => true]]);
        $this->assertTrue($quoted->quoteRequired);
        $this->assertSame(0, $quoted->totalCents);
    }

    public function test_tax_is_rounded_half_up_once_per_active_rate(): void
    {
        TaxRate::factory()->create(['name' => 'GST', 'rate_bps' => 500]);
        TaxRate::factory()->create(['name' => 'PST', 'rate_bps' => 700]);
        TaxRate::factory()->create(['name' => 'Old', 'rate_bps' => 1300, 'is_active' => false]);

        $price = $this->priceFor(Service::factory()->fixed(1010)->create());

        $this->assertSame([51, 71], array_column($price->taxes, 'amount_cents'));
        $this->assertSame(1132, $price->totalCents);
    }
}
