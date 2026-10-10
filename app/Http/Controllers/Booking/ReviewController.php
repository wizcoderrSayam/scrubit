<?php

namespace App\Http\Controllers\Booking;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\SubmitBookingRequest;
use App\Models\Booking;
use App\Services\BookingAccess;
use App\Services\BookingDraftService;
use App\Services\BookingService;
use App\Services\Chat\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private BookingDraftService $drafts,
        private BookingService $bookings,
        private ChatService $chat,
        private BookingAccess $access,
    ) {}

    /**
     * Step 3: review everything (with server-side pricing) before payment.
     */
    public function show(Booking $booking, Request $request): View|RedirectResponse
    {
        if (! $this->drafts->canAccess($booking, $request)) {
            abort_unless($this->access->canView($booking, $request), 404);

            return to_route('bookings.show', $booking);
        }

        if (! $this->chat->isComplete($booking)) {
            return to_route('booking.customize', $booking)
                ->with('status', 'Please answer the remaining questions before reviewing your booking.');
        }

        $booking->load(['service', 'address', 'addons', 'conversation']);

        return view('booking.review', [
            'booking' => $booking,
            'price' => $this->bookings->priceFor($booking),
            'steps' => $this->chat->state($booking)['steps'],
        ]);
    }

    public function submit(SubmitBookingRequest $request, Booking $booking): RedirectResponse
    {
        abort_unless($this->drafts->canAccess($booking, $request), 404);

        $token = $this->bookings->submit($booking, $request->user());
        $this->access->grant($booking, $request);

        if ($booking->status === BookingStatus::AwaitingPayment) {
            return to_route('checkout.show', $booking);
        }

        return to_route('bookings.show', ['booking' => $booking, 'token' => $token]);
    }
}
