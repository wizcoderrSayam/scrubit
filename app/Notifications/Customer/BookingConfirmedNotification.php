<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class BookingConfirmedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public Payment $payment, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.booking_confirmed';
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
        return $this->bookingUrl;
    }
}
