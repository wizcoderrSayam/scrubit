<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single checkout attempt. Written only by PaymentService. Never stores card data.
 */
#[Fillable(['booking_quote_id', 'provider', 'provider_ref', 'idempotency_key', 'amount_cents', 'currency', 'status', 'checkout_url', 'checkout_expires_at', 'meta'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'refunded_cents' => 'integer',
            'success_guard' => 'integer',
            'needs_attention' => 'boolean',
            'meta' => 'array',
            'checkout_expires_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<BookingQuote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(BookingQuote::class, 'booking_quote_id');
    }

    /**
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest('id');
    }

    public function refundableCents(): int
    {
        return in_array($this->status, [PaymentStatus::Succeeded, PaymentStatus::PartiallyRefunded], true)
            ? $this->amount_cents - $this->refunded_cents
            : 0;
    }
}
