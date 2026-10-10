<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingHistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $bookings = $request->user()->bookings()
            ->submitted()
            ->with('currentQuote')
            ->latest('submitted_at')
            ->paginate(10);

        $drafts = $request->user()->bookings()->where('status', 'draft')->latest('updated_at')->limit(5)->get();

        return view('account.bookings', ['bookings' => $bookings, 'drafts' => $drafts]);
    }
}
