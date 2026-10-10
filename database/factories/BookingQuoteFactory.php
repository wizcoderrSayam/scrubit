<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Booking;
use App\Models\BookingQuote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingQuote>
 */
class BookingQuoteFactory extends Factory
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
            'subtotal_cents' => 20000,
            'tax_cents' => 1000,
            'total_cents' => 21000,
            'currency' => 'CAD',
            'description' => 'Deep clean of a two-bedroom apartment.',
            'status' => QuoteStatus::Sent,
            'expires_at' => now()->addDays(7),
            'sent_at' => now(),
        ];
    }
}
