<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;

class CancellationRequestedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking) {}

    protected function templateKey(): string
    {
        return 'admin.cancellation_requested';
    }

    protected function values(): array
    {
        return $this->bookingValues($this->booking);
    }

    protected function actionUrl(): ?string
    {
        return route('admin.bookings.show', $this->booking);
    }

    protected function actionText(): string
    {
        return 'Review request';
    }
}
