<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class BookingCancelledNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public ?int $refundCents) {}

    protected function templateKey(): string
    {
        return 'admin.booking_cancelled';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'refund_note' => $this->refundCents
                ? 'A refund of '.Money::format($this->refundCents, $this->booking->currency).' was issued.'
                : 'No refund was issued.',
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
