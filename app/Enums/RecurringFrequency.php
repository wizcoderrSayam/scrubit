<?php

namespace App\Enums;

enum RecurringFrequency: string
{
    case OneTime = 'one_time';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One-time visit',
            self::Weekly => 'Weekly',
            self::Biweekly => 'Bi-weekly',
            self::Monthly => 'Monthly',
        };
    }
}
