<?php

namespace App\Services\ImportExport;

use DateTimeInterface;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Reads the first sheet of a CSV or XLSX file. The header row is required and must match the dataset.
 */
final class SpreadsheetReader
{
    public const int MAX_ROWS = 2000;

    public const int MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param  list<string>  $expectedHeaders
     * @return list<array<string, string>>
     */
    public function rows(string $path, string $extension, array $expectedHeaders): array
    {
        $extension = strtolower($extension);

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            throw new ImportException('Upload a CSV or Excel (.xlsx) file.');
        }

        $bytes = filesize($path);

        if ($bytes === false || $bytes > self::MAX_BYTES) {
            throw new ImportException('The file must be 5 MB or smaller.');
        }

        $reader = $extension === 'xlsx'
            ? new XlsxReader(new XlsxOptions(SHOULD_PRESERVE_EMPTY_ROWS: true))
            : new CsvReader(new CsvOptions(SHOULD_PRESERVE_EMPTY_ROWS: true));

        $opened = false;

        try {
            $reader->open($path);
            $opened = true;
            $parsed = [];
            $headers = null;
            $dataRows = 0;
            $line = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $line++;
                    $values = array_map($this->stringify(...), $row->toArray());

                    if ($headers === null) {
                        $headers = $this->normalizeHeader($values);

                        if ($headers !== $expectedHeaders) {
                            throw new ImportException('The header row does not match this dataset. Download a blank template and keep its column names.');
                        }

                        continue;
                    }

                    if ($this->isBlank($values)) {
                        continue;
                    }

                    $dataRows++;

                    if ($dataRows > self::MAX_ROWS) {
                        throw new ImportException('A file can contain at most 2,000 rows.');
                    }

                    $assoc = ['__row' => (string) $line];

                    foreach ($expectedHeaders as $index => $header) {
                        $assoc[$header] = $values[$index] ?? '';
                    }

                    $parsed[] = $assoc;
                }

                break;
            }

            if ($headers === null) {
                throw new ImportException('The file is missing a header row.');
            }

            return $parsed;
        } catch (ImportException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new ImportException('The file could not be read. Upload a CSV or Excel (.xlsx) file.');
        } finally {
            if ($opened) {
                $reader->close();
            }
        }
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function normalizeHeader(array $values): array
    {
        if ($values !== []) {
            $values[0] = preg_replace('/^\xEF\xBB\xBF/', '', $values[0]) ?? $values[0];
        }

        $values = array_map(trim(...), $values);

        while ($values !== [] && end($values) === '') {
            array_pop($values);
        }

        return array_values($values);
    }

    /**
     * @param  list<string>  $values
     */
    private function isBlank(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    private function stringify(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) === $value) {
                return (string) (int) $value;
            }

            return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
        }

        return trim((string) ($value ?? ''));
    }
}
