<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServicePricingMethod;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Support\Lines;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveServiceRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $service = $this->route('service');

        return [
            'service_category_id' => ['required', 'integer', Rule::exists(ServiceCategory::class, 'id')],
            ...self::contentRules($service?->id, $this->input('pricing_method')),
            'chat_greeting' => ['nullable', 'string', 'max:500'],
            'chat_needs_prompt' => ['nullable', 'string', 'max:500'],
            'suggested_prompts' => ['nullable', 'string', 'max:3000'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', Rule::exists(ServiceAddon::class, 'id')],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=200,min_height=150,max_width=6000,max_height=6000'],
            'remove_featured_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Shared field rules for the service form and the spreadsheet importer.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function contentRules(?int $ignoreId, mixed $pricingMethod): array
    {
        $money = ['nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/'];
        $method = is_string($pricingMethod) ? $pricingMethod : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique(Service::class, 'slug')->ignore($ignoreId)],
            'short_description' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:20000'],
            'inclusions' => ['nullable', 'string', 'max:5000'],
            'exclusions' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'pricing_method' => ['required', Rule::enum(ServicePricingMethod::class)],
            'price' => [...$money, Rule::requiredIf($method === ServicePricingMethod::Fixed->value)],
            'hourly_rate' => [...$money, Rule::requiredIf($method === ServicePricingMethod::Hourly->value)],
            'estimated_minutes' => ['nullable', 'integer', 'min:15', 'max:2880', Rule::requiredIf($method === ServicePricingMethod::Hourly->value)],
            'allows_recurring' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.regex' => 'Enter the price in dollars, e.g. 150 or 149.99.',
            'hourly_rate.regex' => 'Enter the rate in dollars, e.g. 45 or 45.50.',
            'price.required' => 'A fixed price is required for fixed-price services.',
            'hourly_rate.required' => 'An hourly rate is required for hourly services.',
            'estimated_minutes.required' => 'Hourly services need an estimated duration to calculate the price.',
        ];
    }

    /**
     * Validated input mapped to Service attributes.
     *
     * @return array<string, mixed>
     */
    public function serviceAttributes(): array
    {
        $data = $this->validated();

        return [
            'service_category_id' => $data['service_category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'],
            'description' => $data['description'] ?? null,
            'inclusions' => Lines::toList($data['inclusions'] ?? null),
            'exclusions' => Lines::toList($data['exclusions'] ?? null),
            'instructions' => $data['instructions'] ?? null,
            'pricing_method' => $data['pricing_method'],
            'price_cents' => filled($data['price'] ?? null) ? Money::parse($data['price']) : null,
            'hourly_rate_cents' => filled($data['hourly_rate'] ?? null) ? Money::parse($data['hourly_rate']) : null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
            'allows_recurring' => $this->boolean('allows_recurring'),
            'chat_script' => [
                'greeting' => $data['chat_greeting'] ?? null,
                'needs_prompt' => $data['chat_needs_prompt'] ?? null,
                'suggested_prompts' => Lines::toList($data['suggested_prompts'] ?? null, 8),
            ],
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
