<?php

namespace App\Http\Requests\Admin;

use App\Enums\AddonPricingMethod;
use App\Models\ServiceAddon;
use App\Support\Money;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveAddonRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name')))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique(ServiceAddon::class, 'slug')->ignore($this->route('addon')?->id)],
            'description' => ['nullable', 'string', 'max:300'],
            'pricing_method' => ['required', Rule::enum(AddonPricingMethod::class)],
            'price' => [
                Rule::requiredIf($this->input('pricing_method') !== AddonPricingMethod::Quote->value),
                'nullable', 'string', 'regex:/^\d{1,7}(\.\d{1,2})?$/',
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.regex' => 'Enter the price in dollars, e.g. 25 or 25.50.',
            'price.required' => 'Fixed and hourly add-ons need a price.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addonAttributes(): array
    {
        $data = $this->validated();
        $quoted = $data['pricing_method'] === AddonPricingMethod::Quote->value;

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'pricing_method' => $data['pricing_method'],
            'price_cents' => $quoted || blank($data['price'] ?? null) ? null : Money::parse($data['price']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
