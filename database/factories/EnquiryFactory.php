<?php

namespace Database\Factories;

use App\Enums\EnquiryType;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => EnquiryType::Contact,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '604-555-0100',
            'message' => fake()->sentence(),
        ];
    }
}
