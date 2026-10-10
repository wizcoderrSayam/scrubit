<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
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
            'recipient' => fake()->safeEmail(),
            'subject' => fake()->sentence(),
            'template_key' => 'customer.booking_received_quote',
            'sent_at' => now(),
        ];
    }
}
