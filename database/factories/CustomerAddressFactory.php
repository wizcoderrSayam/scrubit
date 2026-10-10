<?php

namespace Database\Factories;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Home',
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => 'Vancouver',
            'province' => 'BC',
            'postal_code' => 'V6B 1A1',
            'country' => 'CA',
            'is_default' => false,
        ];
    }
}
