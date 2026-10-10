<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingAddress>
 */
class BookingAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'line1' => fake()->streetAddress(),
            'city' => 'Vancouver',
            'province' => 'BC',
            'postal_code' => 'V6B 1A1',
            'country' => 'CA',
        ];
    }
}
