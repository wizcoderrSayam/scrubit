<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\TemplatedNotification;

class PaymentFailedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public Payment $payment, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.payment_failed';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'failure_reason' => $this->payment->failure_reason ?? 'Your payment was declined.',
        ];
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }

    protected function actionText(): string
    {
        return 'Try again';
    }
}
