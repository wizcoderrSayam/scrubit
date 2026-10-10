<?php

namespace App\Http\Requests\Booking;

use App\Enums\RecurringFrequency;
use App\Enums\TimeWindow;
use App\Support\Provinces;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChatWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->route('step')) {
            'frequency' => [
                'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
            ],
            'schedule' => [
                'preferred_date' => ['required', 'date_format:Y-m-d'],
                'time_window' => ['required', Rule::enum(TimeWindow::class)],
            ],
            'address' => [
                'line1' => ['required', 'string', 'max:200'],
                'line2' => ['nullable', 'string', 'max:200'],
                'city' => ['required', 'string', 'max:120'],
                'province' => ['required', Rule::in(array_keys(Provinces::ALL))],
                'postal_code' => ['required', 'string', 'regex:'.Provinces::POSTAL_CODE_PATTERN],
                'notes' => ['nullable', 'string', 'max:500'],
                'save_address' => ['nullable', 'boolean'],
            ],
            'contact' => [
                'contact_name' => ['required', 'string', 'max:120'],
                'contact_email' => ['required', 'email', 'max:255'],
                'contact_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
            ],
            'extras' => [
                'addons' => ['nullable', 'array', 'max:20'],
                'addons.*' => ['integer'],
                'extra_request' => ['nullable', 'string', 'max:1000'],
            ],
            default => ['step' => ['prohibited']],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => 'Enter a valid Canadian postal code, e.g. V6B 1A1.',
            'contact_phone.regex' => 'Enter a valid phone number.',
            'step.prohibited' => 'This step is not part of your booking.',
        ];
    }
}
