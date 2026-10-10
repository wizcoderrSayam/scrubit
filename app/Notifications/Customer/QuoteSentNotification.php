<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Models\BookingQuote;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class QuoteSentNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public BookingQuote $quote, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.quote_sent';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'amount' => Money::format($this->quote->total_cents, $this->quote->currency),
            'quote_description' => $this->quote->description,
            'expires_at' => $this->quote->expires_at->format('F j, Y'),
        ];
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }

    protected function actionText(): string
    {
        return 'Review & pay';
    }
}
