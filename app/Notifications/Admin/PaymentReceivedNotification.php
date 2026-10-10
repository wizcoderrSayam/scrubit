<?php

namespace App\Notifications\Admin;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class PaymentReceivedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public Payment $payment) {}

    protected function templateKey(): string
    {
        return 'admin.payment_received';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'amount' => Money::format($this->payment->amount_cents, $this->payment->currency),
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
