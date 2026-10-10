<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\TemplatedNotification;

class PaymentFailedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public Payment $payment) {}

    protected function templateKey(): string
    {
        return 'admin.payment_failed';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'failure_reason' => $this->payment->failure_reason ?? 'Declined by the payment provider.',
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
