<?php

namespace Tests\Concerns;

use App\Enums\BookingStatus;
use App\Enums\QuoteStatus;
use App\Enums\TimeWindow;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Models\Payment;
use App\Models\Service;
use App\Services\Payments\DemoGateway;
use App\Services\Payments\GatewayEventType;
use Illuminate\Testing\TestResponse;

trait BuildsBookings
{
    /**
     * A draft with every chat step answered, ready for review.
     */
    protected function completedDraft(?Service $service = null, array $attributes = []): Booking
    {
        $booking = Booking::factory()->create([
            'service_id' => ($service ?? Service::factory()->create())->id,
            'status' => BookingStatus::Draft,
            'draft_token_hash' => hash('sha256', str_repeat('a', 40)),
            'preferred_date' => now()->addDays(5)->toDateString(),
            'time_window' => TimeWindow::Morning,
            'chat_state' => ['needs_done' => true, 'extras_done' => true],
            ...$attributes,
        ]);

        $booking->address()->create([
            'line1' => '123 Main Street',
            'city' => 'Vancouver',
            'province' => 'BC',
            'postal_code' => 'V6B 1A1',
            'country' => 'CA',
        ]);

        return $booking;
    }

    /**
     * A booking awaiting payment of an accepted quote.
     *
     * @return array{0: Booking, 1: BookingQuote}
     */
    protected function bookingWithAcceptedQuote(int $totalCents = 21000): array
    {
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create();
        $quote = BookingQuote::factory()->for($booking)->create([
            'subtotal_cents' => $totalCents - 1000,
            'tax_cents' => 1000,
            'total_cents' => $totalCents,
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return [$booking, $quote];
    }

    protected function draftCookieToken(): string
    {
        return str_repeat('a', 40);
    }

    /**
     * Deliver a correctly signed demo webhook to the public endpoint.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function sendDemoWebhook(Payment $payment, GatewayEventType $type, array $overrides = [], ?string $eventId = null): TestResponse
    {
        $gateway = app(DemoGateway::class);
        $body = json_encode([
            'id' => $eventId ?? 'evt_'.uniqid(),
            'type' => $type->value,
            'payment_ref' => $payment->provider_ref,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            ...$overrides,
        ]);

        return $this->call('POST', route('webhooks.payments', 'demo'), server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_SIGNATURE' => $gateway->sign($body, time()),
        ], content: $body);
    }
}
