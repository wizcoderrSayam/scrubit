<?php

namespace Tests\Feature\Snapshots;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders admin pages to static HTML for visual review. Skipped unless SNAPSHOT_DIR is set:
 *   SNAPSHOT_DIR=/path php artisan test --filter=AdminSnapshotTest
 */
class AdminSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_admin_pages_to_html_files(): void
    {
        $directory = getenv('SNAPSHOT_DIR');

        if (! $directory) {
            $this->markTestSkipped('Set SNAPSHOT_DIR to render admin snapshots.');
        }

        @mkdir($directory, 0777, true);

        $admin = User::factory()->admin()->create(['name' => 'Scrubit Admin']);
        $services = Service::factory()->count(4)->create();
        User::factory()->count(3)->create();
        $booking = Booking::factory()->submitted(BookingStatus::PendingQuote)->create(['service_id' => $services[0]->id, 'service_name' => $services[0]->name]);
        $quoted = Booking::factory()->submitted(BookingStatus::AwaitingQuotePayment)->create(['service_id' => $services[1]->id, 'service_name' => $services[1]->name]);
        BookingQuote::factory()->for($quoted)->create();
        $confirmed = Booking::factory()->submitted(BookingStatus::Confirmed)->create(['service_id' => $services[2]->id, 'service_name' => $services[2]->name, 'total_cents' => 25200]);
        Payment::factory()->for($confirmed)->succeeded()->create(['amount_cents' => 25200, 'success_guard' => $confirmed->id]);

        $pages = [
            'dashboard' => route('admin.dashboard'),
            'bookings' => route('admin.bookings.index'),
            'booking-detail' => route('admin.bookings.show', $booking),
            'quotes' => route('admin.quotes.index'),
            'payments' => route('admin.payments.index'),
            'customers' => route('admin.customers.index'),
            'services' => route('admin.services.index'),
            'service-form' => route('admin.services.edit', $services[0]),
            'settings' => route('admin.settings.business.edit'),
        ];

        foreach ($pages as $name => $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            file_put_contents("{$directory}/{$name}.html", $html);
        }

        $this->assertFileExists("{$directory}/dashboard.html");
    }
}
