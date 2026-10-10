<?php

namespace Database\Factories;

use App\Enums\AddonPricingMethod;
use App\Models\ServiceAddon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceAddon>
 */
class ServiceAddonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Inside '.fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'pricing_method' => AddonPricingMethod::Fixed,
            'price_cents' => 2500,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function hourly(int $rateCents = 2500): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_method' => AddonPricingMethod::Hourly,
            'price_cents' => $rateCents,
        ]);
    }

    public function quoted(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_method' => AddonPricingMethod::Quote,
            'price_cents' => null,
        ]);
    }
}
