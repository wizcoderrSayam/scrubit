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
        abort_unless($service->isVisibleInCatalog(), 404);

        $booking = $drafts->startOrResume($service, $request);

        return to_route('booking.customize', $booking);
    }
}
