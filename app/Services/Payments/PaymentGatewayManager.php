<?php

namespace App\Services\Payments;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Resolves the payment gateway selected by PAYMENT_DRIVER.
 */
class PaymentGatewayManager
{
    public function __construct(private Container $container) {}

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payments.driver');
        $class = config("payments.drivers.{$name}");

        if (! is_string($class) || ! is_subclass_of($class, PaymentGateway::class)) {
            throw new InvalidArgumentException("Payment driver [{$name}] is not configured.");
        }

        return $this->container->make($class);
    }

    public function isConfigured(string $name): bool
    {
        return is_string(config("payments.drivers.{$name}"));
    }
}
