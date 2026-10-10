<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;

class VisitScheduledNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.visit_scheduled';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'scheduled_at' => $this->booking->scheduled_at?->format('l, F j, Y \a\t g:i a') ?? 'To be confirmed',
        ];
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }
}
