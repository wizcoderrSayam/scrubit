<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class BookingCancelledNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public ?int $refundCents) {}

    protected function templateKey(): string
    {
        return 'customer.booking_cancelled';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'refund_note' => $this->refundCents
                ? 'A refund of '.Money::format($this->refundCents, $this->booking->currency).' has been issued to your original payment method.'
                : 'No payment was taken for this booking.',
        ];
    }
}
