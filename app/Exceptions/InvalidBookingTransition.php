<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use RuntimeException;

class InvalidBookingTransition extends RuntimeException
{
    public static function between(BookingStatus $from, BookingStatus $to): self
    {
        return new self("A booking cannot move from {$from->label()} to {$to->label()}.");
    }
}
