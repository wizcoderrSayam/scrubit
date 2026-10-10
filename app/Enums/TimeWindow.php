<?php

namespace App\Enums;

enum TimeWindow: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morning (8am – 12pm)',
            self::Afternoon => 'Afternoon (12pm – 4pm)',
            self::Evening => 'Evening (4pm – 8pm)',
        };
    }
}
