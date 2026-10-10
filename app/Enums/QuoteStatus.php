<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Expired => 'Expired',
            self::Superseded => 'Superseded',
        };
    }

    public function isCurrent(): bool
    {
        return in_array($this, [self::Sent, self::Accepted], true);
    }
}
