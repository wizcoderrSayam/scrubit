<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Models\BookingQuote;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class QuoteSentCopyNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public BookingQuote $quote) {}

    protected function templateKey(): string
    {
        return 'admin.quote_sent';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'amount' => Money::format($this->quote->total_cents, $this->quote->currency),
            'expires_at' => $this->quote->expires_at->format('F j, Y'),
        ];
    }

    protected function actionUrl(): ?string
    {
        return route('admin.bookings.show', $this->booking);
    }

    protected function actionText(): string
    {
        return 'Open in admin';
    }
}
