<?php

namespace App\Notifications\Customer;

use App\Models\Booking;
use App\Notifications\TemplatedNotification;
use App\Support\Money;

class RefundIssuedNotification extends TemplatedNotification
{
    public function __construct(public Booking $booking, public int $amountCents, public string $currency, public string $bookingUrl) {}

    protected function templateKey(): string
    {
        return 'customer.refund_issued';
    }

    protected function values(): array
    {
        return [
            ...$this->bookingValues($this->booking),
            'amount' => Money::format($this->amountCents, $this->currency),
        ];
    }

    protected function actionUrl(): ?string
    {
        return $this->bookingUrl;
    }
}
