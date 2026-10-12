<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public const CURRENCIES = ['CAD', 'USD'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:120'],
            'business_tagline' => ['nullable', 'string', 'max:160'],
            'business_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+().\s-]{7,30}$/'],
            'business_email' => ['required', 'email', 'max:255'],
            'business_location' => ['nullable', 'string', 'max:120'],
            'business_currency' => ['required', Rule::in(self::CURRENCIES)],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=120,max_width=4000,max_height=4000'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:min_width=32,min_height=32,max_width=2000,max_height=2000'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'social_facebook' => ['nullable', 'url:https', 'max:255'],
            'social_instagram' => ['nullable', 'url:https', 'max:255'],
            'social_youtube' => ['nullable', 'url:https', 'max:255'],
            'addon_note_text' => ['required', 'string', 'max:120'],
            'addon_note_rate' => ['required', 'string', 'regex:/^\d{1,5}(\.\d{1,2})?$/'],
            'cancellation_cutoff_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'booking_min_lead_days' => ['required', 'integer', 'min:0', 'max:60'],
            'booking_max_advance_days' => ['required', 'integer', 'min:7', 'max:365', 'gt:booking_min_lead_days'],
            'quote_default_expiry_days' => ['required', 'integer', 'min:1', 'max:90'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'addon_note_rate.regex' => 'Enter the rate in dollars, e.g. 25 or 25.50.',
            'business_phone.regex' => 'Enter a valid phone number.',
        ];
    }

    /**
     * The validated input mapped to business setting keys.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $validated = $this->validated();

        return [
            'business.name' => $validated['business_name'],
            'business.tagline' => $validated['business_tagline'] ?? null,
            'business.phone' => $validated['business_phone'],
            'business.email' => $validated['business_email'],
            'business.location' => $validated['business_location'] ?? null,
            'business.currency' => $validated['business_currency'],
            'mail.host' => $validated['mail_host'],
            'mail.port' => (int) $validated['mail_port'],
            'mail.username' => $validated['mail_username'] ?? '',
            'mail.from_address' => $validated['mail_from_address'] ?? '',
            'social.facebook' => $validated['social_facebook'] ?? null,
            'social.instagram' => $validated['social_instagram'] ?? null,
            'social.youtube' => $validated['social_youtube'] ?? null,
            'addon_note.text' => $validated['addon_note_text'],
            'addon_note.rate_cents' => Money::parse($validated['addon_note_rate']),
            'cancellation.cutoff_hours' => (int) $validated['cancellation_cutoff_hours'],
            'booking.min_lead_days' => (int) $validated['booking_min_lead_days'],
            'booking.max_advance_days' => (int) $validated['booking_max_advance_days'],
            'quote.default_expiry_days' => (int) $validated['quote_default_expiry_days'],
        ];
    }
}
