<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MessageType;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\RecurringFrequency;
use App\Enums\ServicePricingMethod;
use App\Enums\TimeWindow;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Status, reference, token hashes and money columns are never mass assignable;
 * they are set explicitly by the booking services.
 */
#[Fillable(['preferred_date', 'time_window', 'frequency', 'contact_name', 'contact_email', 'contact_phone'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'currency' => 'CAD',
        'subtotal_cents' => 0,
        'discount_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 0,
        'quote_required' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'pricing_method' => ServicePricingMethod::class,
            'time_window' => TimeWindow::class,
            'frequency' => RecurringFrequency::class,
            'preferred_date' => 'date',
            'scheduled_at' => 'datetime',
            'unit_price_cents' => 'integer',
            'hourly_rate_cents' => 'integer',
            'estimated_minutes' => 'integer',
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'pricing_terms' => 'array',
            'tax_breakdown' => 'array',
            'chat_state' => 'array',
            'quote_required' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function repeatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repeated_from_id');
    }

    /**
     * @return HasOne<BookingAddress, $this>
     */
    public function address(): HasOne
    {
        return $this->hasOne(BookingAddress::class);
    }

    /**
     * @return HasMany<BookingAddon, $this>
     */
    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    /**
     * @return HasMany<BookingMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BookingMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Messages the customer may see (internal notes excluded).
     *
     * @return HasMany<BookingMessage, $this>
     */
    public function conversation(): HasMany
    {
        return $this->messages()->where('type', '!=', MessageType::InternalNote);
    }

    /**
     * @return HasMany<BookingQuote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(BookingQuote::class)->latest('id');
    }

    /**
     * @return HasOne<BookingQuote, $this>
     */
    public function currentQuote(): HasOne
    {
        return $this->hasOne(BookingQuote::class)
            ->whereIn('status', [QuoteStatus::Sent, QuoteStatus::Accepted])
            ->latestOfMany();
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->whereNotNull('success_guard');
    }

    /**
     * @return HasMany<BookingStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<EmailLog, $this>
     */
    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest('sent_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', '!=', BookingStatus::Draft);
    }

    public function hasOpenPayment(): bool
    {
        return $this->payments()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])->exists();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id !== null && $this->user_id === $user->getKey();
    }

    /**
     * The amount currently payable for this booking, or null when nothing can be charged.
     */
    public function payableAmountCents(): ?int
    {
        return match ($this->status) {
            BookingStatus::AwaitingPayment => $this->total_cents > 0 ? $this->total_cents : null,
            BookingStatus::AwaitingQuotePayment => $this->currentQuote?->isPayable() ? $this->currentQuote->total_cents : null,
            default => null,
        };
    }
}
