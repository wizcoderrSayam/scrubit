@php
    $formats = ['csv' => 'CSV', 'xlsx' => 'Excel (.xlsx)'];
    $statusOptions = collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all();
    $importable = array_values(array_filter($datasets, fn ($dataset) => $dataset->importable()));
    $importOptions = collect($importable)->mapWithKeys(fn ($dataset) => [$dataset->value => $dataset->label()])->all();
@endphp

<x-layouts.admin title="Import / Export" subtitle="Move catalog data in bulk, or download bookings.">
    @if (session('import_errors'))
        <x-ui.alert type="error" class="mb-6">
            <p class="font-semibold">Nothing was imported. Fix these rows and upload the file again.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach (session('import_errors') as $row => $messages)
                    <li>Row {{ $row }}: {{ implode(' ', $messages) }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($datasets as $dataset)
            <x-ui.card :title="'Export '.$dataset->label()" :description="$dataset->notes()">
                <form method="GET" action="{{ route('admin.import-export.export') }}" class="grid gap-4 sm:grid-cols-2">
                    <input type="hidden" name="dataset" value="{{ $dataset->value }}">
                    <x-ui.select :id="'export-format-'.$dataset->value" name="format" label="Format" :options="$formats" value="csv" />
                    @if ($dataset === \App\Services\ImportExport\Dataset::Bookings)
                        <x-ui.select id="booking-status" name="status" label="Status" :options="$statusOptions" placeholder="All except drafts" />
                        <x-ui.input id="booking-from" name="from" label="Submitted from" type="date" />
                        <x-ui.input id="booking-to" name="to" label="Submitted to" type="date" />
                    @endif
                    <div class="sm:col-span-2">
                        <x-ui.button>Download {{ $dataset->label() }}</x-ui.button>
                    </div>
                </form>
                <p class="mt-4 text-xs text-ink-500">Columns: {{ implode(', ', $dataset->headers()) }}</p>
            </x-ui.card>
        @endforeach

        <x-ui.card title="Import" description="Rows are checked first. If any row has an error, nothing is saved. Matching slugs are updated; everything else is left in place." class="xl:col-span-2">
            <form method="POST" action="{{ route('admin.import-export.import') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <x-ui.select name="dataset" label="Dataset" :options="$importOptions" :value="old('dataset', 'categories')" required />
                <x-ui.input name="file" label="CSV or Excel file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required hint="2,000 rows and 5 MB maximum. Use 1 or 0 for yes/no columns." />
                <div class="sm:col-span-2">
                    <x-ui.button>Import file</x-ui.button>
                </div>
            </form>
            <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                @foreach ($importable as $dataset)
                    <a href="{{ route('admin.import-export.template', ['dataset' => $dataset->value, 'format' => 'csv']) }}" class="font-medium text-admin-accent hover:underline">{{ $dataset->label() }} CSV template</a>
                    <a href="{{ route('admin.import-export.template', ['dataset' => $dataset->value, 'format' => 'xlsx']) }}" class="font-medium text-admin-accent hover:underline">{{ $dataset->label() }} Excel template</a>
                @endforeach
            </div>
        </x-ui.card>
    </div>
</x-layouts.admin>
