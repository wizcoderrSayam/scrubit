<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Customer\RefundIssuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_refund_notifies_the_customer(): void
    {
        Notification::fake();
        $booking = Booking::factory()->submitted()->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['amount_cents' => 20000, 'success_guard' => $booking->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.payments.refund', $payment), ['amount' => '50.00'])
            ->assertRedirect();

        $this->assertSame(5000, $payment->fresh()->refunded_cents);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        Notification::assertSentOnDemand(RefundIssuedNotification::class);
    }

    public function test_forbids_customers_from_issuing_refunds(): void
    {
        $booking = Booking::factory()->submitted()->create();
        $payment = Payment::factory()->for($booking)->succeeded()->create(['success_guard' => $booking->id]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.payments.refund', $payment), ['amount' => '50.00'])
            ->assertForbidden();
    }
}
