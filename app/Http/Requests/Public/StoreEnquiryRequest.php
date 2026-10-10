<?php

namespace App\Http\Requests\Public;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
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
        $isQuote = $this->routeIs('quote.store');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => [$isQuote ? 'required' : 'nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
            'subject' => ['nullable', 'string', 'max:200'],
            'service_id' => ['nullable', 'integer', Rule::exists(Service::class, 'id')->where('is_active', true)->whereNull('deleted_at')],
            'message' => [$isQuote ? 'nullable' : 'required', 'string', 'max:3000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number.',
            'website.prohibited' => 'Your message could not be sent.',
        ];
    }
}
