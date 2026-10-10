<?php

namespace App\Http\Requests\Admin;

use App\Enums\RecurringFrequency;
use App\Enums\TimeWindow;
use App\Support\Provinces;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateBookingDetailsRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
            'preferred_date' => ['required', 'date_format:Y-m-d'],
            'time_window' => ['required', Rule::enum(TimeWindow::class)],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'frequency' => ['nullable', Rule::enum(RecurringFrequency::class)],
            'line1' => ['required', 'string', 'max:200'],
            'line2' => ['nullable', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', Rule::in(array_keys(Provinces::ALL))],
            'postal_code' => ['required', 'string', 'regex:'.Provinces::POSTAL_CODE_PATTERN],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
