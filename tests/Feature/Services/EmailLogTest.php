<?php

namespace Tests\Feature\Services;

use App\Models\Booking;
use App\Models\EmailLog;
use App\Notifications\Customer\BookingReceivedNotification;
use App\Services\BookingNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_a_notification_records_an_email_log(): void
    {
        $booking = Booking::factory()->submitted()->create();

        app(BookingNotifier::class)->customer($booking, new BookingReceivedNotification($booking, 'https://example.test/booking'));

        $log = EmailLog::query()->sole();
        $this->assertSame($booking->id, $log->booking_id);
        $this->assertSame($booking->contact_email, $log->recipient);
        $this->assertSame('customer.booking_received_quote', $log->template_key);
        $this->assertNotSame('', $log->subject);
    }
}
