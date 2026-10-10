<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;

class BookingCompletedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.booking_completed';
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
        return 'View booking summary';
    }
}
