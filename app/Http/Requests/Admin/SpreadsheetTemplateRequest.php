<?php

namespace App\Http\Requests\Admin;

use App\Services\ImportExport\Dataset;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class SpreadsheetTemplateRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dataset' => ['required', Rule::enum(Dataset::class), $this->datasetIsImportable()],
            'format' => ['required', Rule::in(['csv', 'xlsx'])],
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
