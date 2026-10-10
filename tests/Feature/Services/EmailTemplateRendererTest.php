<?php

namespace Tests\Feature\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Notifications\Customer\QuoteSentNotification;
use App\Services\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_default_template_and_substitutes_placeholders(): void
    {
        $rendered = app(EmailTemplateRenderer::class)->render('customer.booking_received_quote', [
            'customer_name' => 'Jamie',
            'service' => 'Home Cleaning',
            'reference' => 'SCR-ABC12345',
        ]);

        $this->assertSame('We received your booking SCR-ABC12345', $rendered['subject']);
        $this->assertStringContainsString('<strong>Home Cleaning</strong>', (string) $rendered['html']);
        $this->assertStringContainsString('Hi Jamie', (string) $rendered['html']);
    }

    public function test_escapes_customer_supplied_values_in_the_email(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create(['contact_name' => "O'Reilly <script>alert('x')</script>"]);
        $quote = BookingQuote::factory()->for($booking)->create(['description' => '<b>bold</b> deep clean']);

        $html = (new QuoteSentNotification($booking, $quote, 'https://example.test/link'))->toMail($booking)->render();

        $this->assertStringContainsString('&lt;script&gt;', (string) $html);
        $this->assertStringNotContainsString("<script>alert('x')</script>", (string) $html);
        $this->assertStringNotContainsString('<b>bold</b>', (string) $html);
        $this->assertStringContainsString('https://example.test/link', (string) $html);
    }
}
