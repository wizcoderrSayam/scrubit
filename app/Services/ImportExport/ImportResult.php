<?php

namespace App\Services\ImportExport;

/**
 * Outcome of one service import. Errors mean nothing was written.
 */
final class ImportResult
{
    /**
     * @param  array<int, list<string>>  $errors  spreadsheet row number => messages
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
