<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = (string) Str::uuid();

        return [
            'booking_id' => Booking::factory(),
            'provider' => 'demo',
            'provider_ref' => 'demo_'.$key,
            'idempotency_key' => $key,
            'amount_cents' => 21000,
            'currency' => 'CAD',
            'status' => PaymentStatus::Pending,
            'checkout_expires_at' => now()->addMinutes(30),
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Succeeded,
            'paid_at' => now(),
        ]);
    }
}
