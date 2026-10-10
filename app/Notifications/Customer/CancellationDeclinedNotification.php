<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;

class CancellationDeclinedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.cancellation_declined';
    }

    protected function values(): array
    {
        return $this->bookingValues($this->booking);
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }
}
