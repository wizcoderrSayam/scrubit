<?php

namespace App\Services\ImportExport;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV or XLSX download. Cells that start with a formula character are prefixed so spreadsheet apps treat them as text.
 */
final class SpreadsheetWriter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string|null>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentType = $extension === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($extension, $headers, $rows): void {
            $path = tempnam(sys_get_temp_dir(), 'export');

            if ($path === false) {
                throw new RuntimeException('Unable to create an export file.');
            }

            try {
                $writer = $extension === 'xlsx' ? new XlsxWriter : new CsvWriter;
                $writer->openToFile($path);
                $writer->addRow(Row::fromValues($this->escape($headers)));

                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues($this->escape($row)));
                }

                $writer->close();
                readfile($path);
            } finally {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }, $filename, ['Content-Type' => $contentType]);
    }

    /**
     * @param  list<string|null>  $cells
     * @return list<string>
     */
    private function escape(array $cells): array
    {
        return array_map(function (?string $cell): string {
            $value = (string) $cell;

            if (preg_match('/^[=+\-@]/', $value) === 1) {
                return "'".$value;
            }

            return $value;
        }, $cells);
    }
}
