<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class BookingTransitionRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'reason' => ['nullable', 'string', 'max:480'],
            'refund' => ['nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['refund.regex' => 'Enter the refund in dollars, e.g. 120 or 120.50.'];
    }
}
