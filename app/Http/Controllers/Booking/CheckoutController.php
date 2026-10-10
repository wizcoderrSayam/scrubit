<?php

namespace App\Http\Controllers\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingAccess;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private BookingAccess $access,
        private PaymentService $payments,
    ) {}

    /**
     * Step 4: payment summary.
     */
    public function show(Booking $booking, Request $request): View|RedirectResponse
    {
        abort_unless($this->access->canView($booking, $request), 404);

        if (! $booking->status->isPayable()) {
            return to_route('bookings.show', $booking);
        }

        $booking->load(['address', 'addons', 'currentQuote', 'payments']);

        return view('checkout.show', [
            'booking' => $booking,
            'quote' => $booking->status === BookingStatus::AwaitingQuotePayment ? $booking->currentQuote : null,
            'amount' => $booking->payableAmountCents(),
            'canAct' => $this->access->canAct($booking, $request),
            'lastFailed' => $booking->payments->first(fn ($payment) => $payment->status === PaymentStatus::Failed),
        ]);
    }

    public function start(Booking $booking, Request $request): RedirectResponse
    {
        abort_unless($this->access->canAct($booking, $request), 404);

        $payment = $this->payments->startCheckout($booking);

        return redirect()->away($payment->checkout_url);
    }

    /**
     * Where the customer lands after the hosted checkout. Shows the stored status only:
     * the booking is confirmed exclusively by verified webhook events.
     */
    public function return(Booking $booking, Request $request): View
    {
        abort_unless($this->access->canView($booking, $request), 404);

        $payment = $booking->payments()->where('idempotency_key', (string) $request->query('payment'))->firstOrFail();

        return view('checkout.result', [
            'booking' => $booking->fresh(),
            'payment' => $payment,
            'cancelled' => false,
        ]);
    }

    public function cancel(Booking $booking, Request $request): View
    {
        abort_unless($this->access->canView($booking, $request), 404);

        $payment = $booking->payments()->where('idempotency_key', (string) $request->query('payment'))->firstOrFail();

        return view('checkout.result', [
            'booking' => $booking->fresh(),
            'payment' => $payment,
            'cancelled' => true,
        ]);
    }
}
