<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Database\Factories\BookingQuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A price approved by an administrator. Written only by QuoteService.
 */
#[Fillable(['subtotal_cents', 'tax_cents', 'total_cents', 'tax_breakdown', 'currency', 'description', 'status', 'expires_at', 'sent_at', 'accepted_at', 'created_by'])]
class BookingQuote extends Model
{
    /** @use HasFactory<BookingQuoteFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'subtotal_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'tax_breakdown' => 'array',
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isExpired(): bool
    {
        return $this->status === QuoteStatus::Expired || $this->expires_at->isPast();
    }

    public function isPayable(): bool
    {
        return $this->status->isCurrent() && ! $this->isExpired();
    }
}
