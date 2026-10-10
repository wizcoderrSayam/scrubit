<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportSpreadsheetRequest;
use App\Http\Requests\Admin\ImportSpreadsheetRequest;
use App\Http\Requests\Admin\SpreadsheetTemplateRequest;
use App\Services\ImportExport\BookingExporter;
use App\Services\ImportExport\CatalogExporter;
use App\Services\ImportExport\CatalogImporter;
use App\Services\ImportExport\Dataset;
use App\Services\ImportExport\ImportException;
use App\Services\ImportExport\SpreadsheetWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function index(): View
    {
        return view('admin.import-export.index', [
            'datasets' => Dataset::cases(),
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function export(
        ExportSpreadsheetRequest $request,
        CatalogExporter $catalog,
        BookingExporter $bookings,
        SpreadsheetWriter $writer,
    ): StreamedResponse {
        $dataset = $request->dataset();
        $format = $request->validated('format');
        $rows = $dataset === Dataset::Bookings
            ? $bookings->rows($request->validated('from'), $request->validated('to'), $request->bookingStatus())
            : $catalog->rows($dataset);

        return $writer->download($dataset->value.'-'.now()->toDateString().'.'.$format, $dataset->headers(), $rows);
    }

    public function import(ImportSpreadsheetRequest $request, CatalogImporter $importer): RedirectResponse
    {
        $file = $request->file('file');
        $dataset = $request->dataset();

        try {
            $result = $importer->import($dataset, $file->getPathname(), $file->getClientOriginalExtension());
        } catch (ImportException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }

        if ($result->failed()) {
            return back()
                ->with('import_errors', $result->errors)
                ->withInput();
        }

        $label = strtolower($dataset->label());

        return back()->with('status', "Imported {$result->created} new {$label} and updated {$result->updated}.");
    }

    public function template(SpreadsheetTemplateRequest $request, SpreadsheetWriter $writer): StreamedResponse
    {
        $dataset = $request->dataset();

        return $writer->download(
            $dataset->value.'-template.'.$request->validated('format'),
            $dataset->headers(),
            [],
        );
    }
}
