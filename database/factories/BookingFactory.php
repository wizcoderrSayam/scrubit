<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\TimeWindow;
use App\Models\Booking;
use App\Models\BookingAddress;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'SCR-'.Str::upper(Str::random(8)),
            'service_id' => Service::factory(),
            'status' => BookingStatus::Draft,
            'service_name' => fn (array $attributes) => Service::find($attributes['service_id'])?->name ?? 'Cleaning',
            'pricing_method' => fn (array $attributes) => Service::find($attributes['service_id'])?->pricing_method ?? 'custom_quote',
            'preferred_date' => now()->addDays(7)->toDateString(),
            'time_window' => TimeWindow::Morning,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '604-555-0100',
            'currency' => 'CAD',
            'quote_required' => true,
        ];
    }

    /**
     * Mark the booking as submitted with the given status and an access token.
     */
    public function submitted(BookingStatus $status = BookingStatus::PendingQuote, string $accessToken = 'test-access-token'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'access_token_hash' => hash('sha256', $accessToken),
            'terms_accepted_at' => now(),
            'submitted_at' => now(),
        ])->afterCreating(function (Booking $booking): void {
            BookingAddress::factory()->for($booking)->create();
        });
    }

    public function priced(int $subtotalCents = 10000, int $taxCents = 500): static
    {
        return $this->state(fn (array $attributes) => [
            'quote_required' => false,
            'subtotal_cents' => $subtotalCents,
            'tax_cents' => $taxCents,
            'total_cents' => $subtotalCents + $taxCents,
        ]);
    }
}
