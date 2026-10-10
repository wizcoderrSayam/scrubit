<?php

namespace App\Models;

use App\Enums\AddonPricingMethod;
use App\Enums\AddonSource;
use Database\Factories\BookingAddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot of an add-on or extra request on a booking. Written only by BookingService/ChatService.
 */
#[Fillable(['service_addon_id', 'name', 'description', 'pricing_method', 'unit_price_cents', 'quantity', 'line_total_cents', 'needs_quote', 'source'])]
class BookingAddon extends Model
{
    /** @use HasFactory<BookingAddonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_method' => AddonPricingMethod::class,
            'source' => AddonSource::class,
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
            'needs_quote' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<ServiceAddon, $this>
     */
    public function serviceAddon(): BelongsTo
    {
        return $this->belongsTo(ServiceAddon::class);
    }
}
