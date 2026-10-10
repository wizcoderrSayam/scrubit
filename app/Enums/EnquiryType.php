<?php

namespace App\Enums;

enum EnquiryType: string
{
    case Contact = 'contact';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact message',
            self::Quote => 'Quote request',
        };
    }
}
