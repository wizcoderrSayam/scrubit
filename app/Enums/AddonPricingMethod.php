<?php

namespace App\Enums;

enum AddonPricingMethod: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed price',
            self::Hourly => 'Per hour',
            self::Quote => 'Quoted by admin',
        };
    }
}
