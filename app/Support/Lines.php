<?php

namespace App\Support;

/**
 * Converts between textarea input (one item per line) and string lists.
 */
final class Lines
{
    /**
     * @return list<string>
     */
    public static function toList(?string $text, int $maxItems = 50): array
    {
        $items = array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text) ?: []);

        return array_slice(array_values(array_filter($items, fn (string $item) => $item !== '')), 0, $maxItems);
    }

    /**
     * @param  array<int, string>|null  $items
     */
    public static function toText(?array $items): string
    {
        return implode("\n", $items ?? []);
    }
}
