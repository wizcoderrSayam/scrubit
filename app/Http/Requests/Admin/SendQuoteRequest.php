<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class SendQuoteRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'description' => ['required', 'string', 'max:3000'],
            'expires_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.regex' => 'Enter the amount before tax in dollars, e.g. 240 or 239.99.',
            'expires_at.after_or_equal' => 'The expiry date cannot be in the past.',
        ];
    }
}
