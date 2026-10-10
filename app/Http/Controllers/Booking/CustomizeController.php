<?php

namespace App\Http\Controllers\Booking;

use App\Enums\RecurringFrequency;
use App\Enums\TimeWindow;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingMessage;
use App\Services\AvailabilityService;
use App\Services\BookingAccess;
use App\Services\BookingDraftService;
use App\Services\Chat\ChatService;
use App\Services\SettingsService;
use App\Support\Provinces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomizeController extends Controller
{
    public function __construct(
        private BookingDraftService $drafts,
        private ChatService $chat,
    ) {}

    /**
     * Step 2: the conversational requirements chat.
     */
    public function __invoke(Booking $booking, Request $request, AvailabilityService $availability, SettingsService $settings, BookingAccess $access): View|RedirectResponse
    {
        if (! $this->drafts->canAccess($booking, $request)) {
            abort_unless($access->canView($booking, $request), 404);

            return to_route('bookings.show', $booking);
        }

        $booking->load(['service.addons' => fn ($query) => $query->active()->orderBy('sort_order'), 'address', 'addons']);
        $state = $this->chat->state($booking);
        $edit = $request->query('edit');
        $editableSteps = array_diff($state['steps'], ['needs', 'property']);

        if (is_string($edit) && in_array($edit, $editableSteps, true) && $this->chat->isStepComplete($booking, $edit)) {
            $state['step'] = $edit;
        }

        $user = $request->user();
        $defaultAddress = $user?->addresses()->first();

        return view('booking.customize', [
            'booking' => $booking,
            'service' => $booking->service,
            'chat' => [
                'messages' => $booking->conversation()->get()->map(fn (BookingMessage $message) => $message->toChatArray())->all(),
                'state' => $state,
                'suggestions' => [
                    'needs' => $booking->service->suggestedPrompts(),
                    'property' => [
                        '2 bedroom, 1 bathroom apartment, about 850 sq ft, in fairly good condition.',
                        '3 bedroom house with 2 bathrooms, around 1,800 sq ft. The kitchen needs extra attention.',
                        'Small office, about 1,200 sq ft, with a kitchenette and two washrooms.',
                    ],
                ],
                'urls' => [
                    'message' => route('booking.messages.store', $booking),
                    'step' => route('booking.steps.store', ['booking' => $booking, 'step' => '__STEP__']),
                    'review' => route('booking.review', $booking),
                ],
                'form' => [
                    'frequency' => $booking->frequency?->value ?? RecurringFrequency::Biweekly->value,
                    'preferred_date' => $booking->preferred_date?->toDateString() ?? '',
                    'time_window' => $booking->time_window?->value ?? TimeWindow::Morning->value,
                    'line1' => $booking->address->line1 ?? $defaultAddress->line1 ?? '',
                    'line2' => $booking->address->line2 ?? $defaultAddress->line2 ?? '',
                    'city' => $booking->address->city ?? $defaultAddress->city ?? '',
                    'province' => $booking->address->province ?? $defaultAddress->province ?? 'BC',
                    'postal_code' => $booking->address->postal_code ?? $defaultAddress->postal_code ?? '',
                    'notes' => $booking->address->notes ?? '',
                    'save_address' => false,
                    'contact_name' => $booking->contact_name ?? $user?->name ?? '',
                    'contact_email' => $booking->contact_email ?? $user?->email ?? '',
                    'contact_phone' => $booking->contact_phone ?? $user?->phone ?? '',
                    'addons' => $booking->addons->pluck('service_addon_id')->filter()->values()->all(),
                    'extra_request' => (string) $booking->addons->firstWhere('source.value', 'chat_flag')?->description,
                ],
            ],
            'frequencies' => collect(RecurringFrequency::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all(),
            'windows' => collect(TimeWindow::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all(),
            'provinces' => Provinces::ALL,
            'addons' => $booking->service->addons->map(fn ($addon) => [
                'id' => $addon->id,
                'name' => $addon->name,
                'description' => $addon->description,
                'price' => $addon->priceLabel((string) $settings->get('business.currency', 'CAD')),
            ])->all(),
            'availability' => [
                'min' => $availability->earliestDate()->toDateString(),
                'max' => $availability->latestDate()->toDateString(),
                'closedWeekdays' => $availability->closedWeekdays(),
                'closedDates' => $availability->closedDates(),
            ],
            'addonNote' => $settings->addonNote(),
            'isLoggedIn' => $user !== null,
        ]);
    }
}
