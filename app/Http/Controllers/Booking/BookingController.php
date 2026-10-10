<?php

namespace App\Http\Controllers\Booking;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Models\Booking;
use App\Services\BookingAccess;
use App\Services\BookingDraftService;
use App\Services\BookingService;
use App\Services\Payments\PaymentService;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Step 5 and beyond: the customer-facing booking details page.
 */
class BookingController extends Controller
{
    public function __construct(
        private BookingAccess $access,
        private BookingService $bookings,
    ) {}

    public function show(Booking $booking, Request $request, BookingDraftService $drafts): View|RedirectResponse
    {
        if ($booking->status === BookingStatus::Draft) {
            abort_unless($drafts->canAccess($booking, $request), 404);

            return to_route('booking.customize', $booking);
        }

        abort_unless($this->access->canView($booking, $request), 404);

        if ($request->has('token') || $request->has('signature')) {
            return to_route('bookings.show', $booking);
        }

        return view('bookings.show', $this->viewData($booking, $request));
    }

    public function print(Booking $booking, Request $request): View
    {
        abort_if($booking->status === BookingStatus::Draft, 404);
        abort_unless($this->access->canView($booking, $request), 404);

        return view('bookings.print', $this->viewData($booking, $request));
    }

    public function acceptQuote(Booking $booking, Request $request, QuoteService $quotes, PaymentService $payments): RedirectResponse
    {
        abort_unless($this->access->canAct($booking, $request), 404);

        $quotes->accept($booking);
        $payment = $payments->startCheckout($booking);

        return redirect()->away($payment->checkout_url);
    }

    public function requestRequote(Booking $booking, Request $request, QuoteService $quotes): RedirectResponse
    {
        abort_unless($this->access->canAct($booking, $request), 404);

        $quotes->requestRequote($booking);

        return to_route('bookings.show', $booking)->with('status', 'We have asked our team for an updated quote.');
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): RedirectResponse
    {
        abort_unless($this->access->canAct($booking, $request), 404);

        $status = $this->bookings->cancelByCustomer($booking, $request->user(), $request->validated('reason'));

        return to_route('bookings.show', $booking)->with('status', $status === BookingStatus::Cancelled
            ? 'Your booking has been cancelled.'
            : 'Your cancellation request has been sent. We will confirm it shortly.');
    }

    /**
     * @return array<string, mixed>
     */
    private function viewData(Booking $booking, Request $request): array
    {
        $booking->load(['service', 'address', 'addons', 'conversation', 'currentQuote', 'payments', 'successfulPayment']);

        return [
            'booking' => $booking,
            'quote' => $booking->currentQuote ?? $booking->quotes()->first(),
            'canAct' => $this->access->canAct($booking, $request),
            'canCancel' => $this->bookings->canCustomerCancel($booking),
        ];
    }
}
