<?php

namespace App\Http\Requests\Customer;

use App\Support\Provinces;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'line1' => ['required', 'string', 'max:200'],
            'line2' => ['nullable', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', Rule::in(array_keys(Provinces::ALL))],
            'postal_code' => ['required', 'string', 'regex:'.Provinces::POSTAL_CODE_PATTERN],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['postal_code.regex' => 'Enter a valid Canadian postal code, e.g. V6B 1A1.'];
    }
}
