<?php

namespace App\Services;

use App\Models\Booking;
use RuntimeException;

/**
 * Generates unguessable, human-friendly booking references such as SCR-7K3M9QXD.
 */
class ReferenceGenerator
{
    /**
     * Crockford base32 alphabet (no I, L, O, U to avoid confusion).
     */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = 'SCR-'.$this->randomString(8);

            if (! Booking::query()->where('reference', $reference)->exists()) {
                return $reference;
            }
        }

        throw new RuntimeException('Unable to generate a unique booking reference.');
    }

    private function randomString(int $length): string
    {
        $characters = '';

        for ($index = 0; $index < $length; $index++) {
            $characters .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $characters;
    }
}
