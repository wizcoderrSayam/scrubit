<?php

namespace Database\Factories;

use App\Enums\ServicePricingMethod;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' cleaning';

        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'inclusions' => ['Dusting', 'Vacuuming'],
            'exclusions' => [],
            'pricing_method' => ServicePricingMethod::CustomQuote,
            'allows_recurring' => false,
            'chat_script' => [
                'greeting' => 'Hi! Tell us what you need.',
                'needs_prompt' => 'What should we focus on?',
                'suggested_prompts' => ['Focus on the kitchen'],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function fixed(int $priceCents = 15000): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_method' => ServicePricingMethod::Fixed,
            'price_cents' => $priceCents,
        ]);
    }

    public function hourly(int $rateCents = 4000, int $estimatedMinutes = 180): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_method' => ServicePricingMethod::Hourly,
            'hourly_rate_cents' => $rateCents,
            'estimated_minutes' => $estimatedMinutes,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => ['allows_recurring' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
