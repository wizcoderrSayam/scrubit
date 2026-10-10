<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_forbids_customers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_counts_bookings_by_status_and_excludes_drafts(): void
    {
        Booking::factory()->create();
        Booking::factory()->submitted(BookingStatus::PendingQuote)->count(2)->create();
        Booking::factory()->submitted(BookingStatus::Cancelled)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['total'] === 3
                && $metrics['pendingQuotes'] === 2
                && $metrics['cancelled'] === 1)
            ->assertSee('Starting $25/hr add on services');
    }

    public function test_revenue_counts_only_collected_payments_net_of_refunds(): void
    {
        $booking = Booking::factory()->submitted(BookingStatus::Confirmed)->create();
        Payment::factory()->for($booking)->succeeded()->create(['amount_cents' => 20000]);
        Payment::factory()->for($booking)->create(['amount_cents' => 50000, 'status' => PaymentStatus::Failed]);
        Payment::factory()->for($booking)->create(['amount_cents' => 70000, 'status' => PaymentStatus::Cancelled]);
        Payment::factory()->for($booking)->create(['amount_cents' => 90000, 'status' => PaymentStatus::Pending]);
        Payment::factory()->for($booking)->create(['amount_cents' => 10000, 'refunded_cents' => 4000, 'status' => PaymentStatus::PartiallyRefunded]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['revenueCents'] === 26000);
    }
}
