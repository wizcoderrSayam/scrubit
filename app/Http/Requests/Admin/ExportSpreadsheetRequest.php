<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use App\Services\ImportExport\Dataset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ExportSpreadsheetRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: null,
            'to' => $this->input('to') ?: null,
            'status' => $this->input('status') ?: null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dataset' => ['required', Rule::enum(Dataset::class)],
            'format' => ['required', Rule::in(['csv', 'xlsx'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
        ];
    }

    public function dataset(): Dataset
    {
        return Dataset::from($this->validated('dataset'));
    }

    public function bookingStatus(): ?BookingStatus
    {
        $status = $this->validated('status');

        return filled($status) ? BookingStatus::from($status) : null;
    }
}
