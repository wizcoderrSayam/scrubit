<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Integer minor-unit (cents) money helpers. Never uses floating point.
 */
final class Money
{
    private const SYMBOLS = [
        'CAD' => '$',
        'USD' => '$',
    ];

    /**
     * Convert a decimal string such as "25", "25.5" or "25.50" into cents.
     *
     * @throws InvalidArgumentException
     */
    public static function parse(string $amount): int
    {
        $amount = trim($amount);

        if (! preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException("Invalid money amount [{$amount}].");
        }

        $fraction = str_pad($matches[2] ?? '0', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }

    /**
     * Render cents as a plain decimal string, e.g. 2550 => "25.50".
     */
    public static function toDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Render cents for display, omitting zero cents: 2500 => "$25", 2550 => "$25.50".
     */
    public static function formatCompact(int $cents, string $currency = 'CAD'): string
    {
        $formatted = self::format($cents, $currency);

        return $cents % 100 === 0 ? substr($formatted, 0, -3) : $formatted;
    }

    /**
     * Render cents for display, e.g. 250000 => "$2,500.00".
     */
    public static function format(int $cents, string $currency = 'CAD'): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $symbol = self::SYMBOLS[strtoupper($currency)] ?? strtoupper($currency).' ';

        return $sign.$symbol.number_format(intdiv($cents, 100)).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
