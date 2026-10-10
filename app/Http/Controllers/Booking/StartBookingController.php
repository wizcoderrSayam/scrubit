<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\BookingDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StartBookingController extends Controller
{
    /**
     * Step 1 → 2: create or resume a draft for the chosen service.
     */
    public function __invoke(Service $service, Request $request, BookingDraftService $drafts): RedirectResponse
    {
        $service->loadMissing('category');
        abort_unless($service->is_active && $service->category?->is_active, 404);

        $booking = $drafts->startOrResume($service, $request);

        return to_route('booking.customize', $booking);
    }
}
