<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendQuoteRequest;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Services\QuoteService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(): View
    {
        return view('admin.quotes.index', [
            'pending' => Booking::query()
                ->where('status', BookingStatus::PendingQuote)
                ->with('service')
                ->oldest('submitted_at')
                ->get(),
            'quotes' => BookingQuote::query()
                ->with(['booking', 'creator'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(SendQuoteRequest $request, Booking $booking, QuoteService $quotes): RedirectResponse
    {
        $quotes->send(
            $booking,
            Money::parse($request->validated('amount')),
            $request->validated('description'),
            CarbonImmutable::createFromFormat('Y-m-d', $request->validated('expires_at'))->endOfDay(),
            $request->user(),
        );

        return to_route('admin.bookings.show', $booking)->with('status', 'Quote sent to the customer.');
    }
}
