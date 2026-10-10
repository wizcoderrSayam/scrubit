<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administration dashboard.
     */
    public function __invoke(SettingsService $settings): View
    {
        $statusCounts = Booking::query()
            ->submitted()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $count = fn (BookingStatus ...$statuses): int => (int) collect($statuses)->sum(fn (BookingStatus $status) => $statusCounts[$status->value] ?? 0);

        return view('admin.dashboard', [
            'metrics' => [
                'total' => (int) $statusCounts->sum(),
                'today' => Booking::query()->submitted()->whereNotIn('status', [BookingStatus::Cancelled])->whereDate('preferred_date', today())->count(),
                'upcoming' => Booking::query()->whereIn('status', [BookingStatus::Confirmed, BookingStatus::InProgress])->whereDate('preferred_date', '>=', today())->count(),
                'pendingQuotes' => $count(BookingStatus::PendingQuote),
                'awaitingPayment' => $count(BookingStatus::AwaitingPayment, BookingStatus::AwaitingQuotePayment),
                'confirmed' => $count(BookingStatus::Confirmed, BookingStatus::InProgress),
                'completed' => $count(BookingStatus::Completed),
                'cancelled' => $count(BookingStatus::Cancelled),
                'cancellationRequests' => $count(BookingStatus::CancellationRequested),
                'revenueCents' => (int) Payment::query()
                    ->whereIn('status', PaymentStatus::collected())
                    ->sum(DB::raw('amount_cents - refunded_cents')),
                'paymentsNeedingAttention' => Payment::query()->where('needs_attention', true)->count(),
                'openEnquiries' => Enquiry::query()->whereNull('handled_at')->count(),
                'customers' => User::query()->customers()->count(),
                'services' => Service::query()->count(),
                'pages' => Page::query()->count(),
            ],
            'recentBookings' => Booking::query()->submitted()->with('service')->latest('submitted_at')->limit(8)->get(),
            'recentPayments' => Payment::query()->with('booking')->latest('id')->limit(8)->get(),
            'currency' => (string) $settings->get('business.currency', 'CAD'),
            'addonNote' => $settings->addonNote(),
        ]);
    }
}
