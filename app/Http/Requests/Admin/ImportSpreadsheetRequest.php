<?php

namespace App\Http\Requests\Admin;

use App\Services\ImportExport\Dataset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ImportSpreadsheetRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dataset' => ['required', Rule::enum(Dataset::class), $this->datasetIsImportable()],
            'file' => ['required', 'file', 'mimes:csv,xlsx,txt', 'extensions:csv,xlsx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a CSV or Excel (.xlsx) file.',
            'file.extensions' => 'Upload a CSV or Excel (.xlsx) file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ];
    }

    public function dataset(): Dataset
    {
        return Dataset::from($this->validated('dataset'));
    }

    private function datasetIsImportable(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $dataset = is_string($value) ? Dataset::tryFrom($value) : null;

            if ($dataset !== null && ! $dataset->importable()) {
                $fail('Bookings can be exported, not imported.');
            }
        };
    }
}
