<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\RecurringFrequency;
use App\Enums\TimeWindow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingTransitionRequest;
use App\Http\Requests\Admin\UpdateBookingDetailsRequest;
use App\Models\Booking;
use App\Models\Service;
use App\Services\AdminBookingService;
use App\Services\BookingService;
use App\Services\QuoteService;
use App\Support\Money;
use App\Support\Provinces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private AdminBookingService $admin) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'service' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $bookings = Booking::query()
            ->submitted()
            ->with(['service', 'successfulPayment', 'currentQuote'])
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(fn ($inner) => $inner
                    ->where('reference', 'like', "%{$term}%")
                    ->orWhere('contact_name', 'like', "%{$term}%")
                    ->orWhere('contact_email', 'like', "%{$term}%")
                    ->orWhere('contact_phone', 'like', "%{$term}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['service'] ?? null, fn ($query, int $serviceId) => $query->where('service_id', $serviceId))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('preferred_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('preferred_date', '<=', $to))
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'statuses' => array_filter(BookingStatus::cases(), fn ($status) => $status !== BookingStatus::Draft),
            'services' => Service::withTrashed()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Booking $booking, QuoteService $quotes, BookingService $bookings): View
    {
        abort_if($booking->status === BookingStatus::Draft, 404);

        $booking->load([
            'service', 'user', 'address', 'addons', 'messages.user', 'quotes.creator',
            'payments', 'statusHistory.user', 'repeatedFrom', 'successfulPayment',
        ]);

        return view('admin.bookings.show', [
            'booking' => $booking,
            'transitions' => $this->admin->manualTransitions($booking),
            'price' => $bookings->priceFor($booking),
            'defaultQuoteExpiry' => $quotes->defaultExpiry()->toDateString(),
            'suggestedQuoteAmount' => $booking->subtotal_cents > 0 ? Money::toDecimal($booking->subtotal_cents) : '',
            'windows' => TimeWindow::cases(),
            'frequencies' => RecurringFrequency::cases(),
            'provinces' => Provinces::ALL,
            'canSendQuote' => in_array($booking->status, [BookingStatus::PendingQuote, BookingStatus::AwaitingQuotePayment], true),
        ]);
    }

    public function update(UpdateBookingDetailsRequest $request, Booking $booking): RedirectResponse
    {
        abort_if($booking->status === BookingStatus::Draft || $booking->status->isFinal(), 403, 'Completed and cancelled bookings cannot be edited.');

        DB::transaction(function () use ($request, $booking): void {
            $booking->forceFill([
                'contact_name' => $request->validated('contact_name'),
                'contact_email' => strtolower($request->validated('contact_email')),
                'contact_phone' => $request->validated('contact_phone'),
                'preferred_date' => $request->validated('preferred_date'),
                'time_window' => $request->validated('time_window'),
                'scheduled_at' => $request->validated('scheduled_at'),
                'frequency' => $request->validated('frequency'),
            ])->save();

            $booking->address()->updateOrCreate([], [
                ...$request->safe()->only(['line1', 'line2', 'city', 'province', 'notes']),
                'postal_code' => strtoupper($request->validated('postal_code')),
                'country' => 'CA',
            ]);
        });

        return to_route('admin.bookings.show', $booking)->with('status', 'Booking details saved.');
    }

    public function transition(BookingTransitionRequest $request, Booking $booking): RedirectResponse
    {
        $refund = filled($request->validated('refund')) ? Money::parse($request->validated('refund')) : 0;

        $this->admin->transition(
            $booking->load('successfulPayment'),
            BookingStatus::from($request->validated('status')),
            $request->user(),
            $request->validated('reason'),
            $refund,
        );

        return to_route('admin.bookings.show', $booking)->with('status', 'Booking status updated.');
    }

    public function note(Request $request, Booking $booking): RedirectResponse
    {
        $note = $request->validate(['note' => ['required', 'string', 'max:3000']])['note'];
        $this->admin->addNote($booking, $request->user(), $note);

        return to_route('admin.bookings.show', $booking)->with('status', 'Internal note added.');
    }

    public function repeat(Request $request, Booking $booking, BookingService $bookings): RedirectResponse
    {
        $validated = $request->validate([
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_window' => ['required', Rule::enum(TimeWindow::class)],
        ]);

        abort_unless(in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::Completed], true), 403);

        $copy = $bookings->repeat($booking, $request->user(), $validated['preferred_date'], $validated['time_window']);

        return to_route('admin.bookings.show', $copy)->with('status', "Repeat booking {$copy->reference} created. Send a quote when ready.");
    }
}
