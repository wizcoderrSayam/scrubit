<?php

namespace App\Enums;

enum ServicePricingMethod: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
    case StartingQuote = 'starting_quote';
    case CustomQuote = 'custom_quote';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed price',
            self::Hourly => 'Hourly rate',
            self::StartingQuote => 'Starting price, final quote',
            self::CustomQuote => 'Custom quote',
        };
    }

    public function requiresQuote(): bool
    {
        return in_array($this, [self::StartingQuote, self::CustomQuote], true);
    }
}
