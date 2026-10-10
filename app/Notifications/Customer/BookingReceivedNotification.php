<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;

class BookingReceivedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return $this->booking->quote_required ? 'customer.booking_received_quote' : 'customer.booking_received_payment';
    }

    protected function values(): array
    {
        return $this->bookingValues($this->booking);
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }

    protected function actionText(): string
    {
        return $this->booking->quote_required ? 'View booking' : 'Pay now';
    }
}
