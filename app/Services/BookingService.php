<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MessageSender;
use App\Enums\MessageType;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Admin\BookingCancelledNotification as AdminBookingCancelledNotification;
use App\Notifications\Admin\CancellationRequestedNotification;
use App\Notifications\Admin\NewBookingNotification;
use App\Notifications\Customer\BookingCancelledNotification;
use App\Notifications\Customer\BookingReceivedNotification;
use App\Services\Chat\ChatService;
use App\Services\Pricing\BookingPricingService;
use App\Services\Pricing\PriceBreakdown;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private BookingStateMachine $stateMachine,
        private BookingPricingService $pricing,
        private ChatService $chat,
        private BookingNotifier $notifier,
    ) {}

    public function priceFor(Booking $booking): PriceBreakdown
    {
        return $this->pricing->calculate($booking);
    }

    /**
     * Submit a completed draft. Prices are recalculated and snapshotted on the server.
     *
     * @return string The plain-text access token for the guest booking link.
     *
     * @throws BookingActionException
     */
    public function submit(Booking $booking, ?User $user): string
    {
        if ($booking->status !== BookingStatus::Draft) {
            throw new BookingActionException('This booking has already been submitted.');
        }

        if (! $this->chat->isComplete($booking)) {
            throw new BookingActionException('Please finish describing your booking before submitting it.');
        }

        $accessToken = Str::random(48);

        DB::transaction(function () use ($booking, $user, $accessToken): void {
            // Lock the draft so in-flight chat updates cannot change it after prices are snapshotted.
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($locked->status !== BookingStatus::Draft) {
                throw new BookingActionException('This booking has already been submitted.');
            }

            $booking->refresh()->load(['service', 'addons']);
            $service = $booking->service;
            $price = $this->pricing->calculate($booking);

            $booking->forceFill([
                'user_id' => $booking->user_id ?? $user?->getKey(),
                'service_name' => $service->name,
                'pricing_method' => $service->pricing_method,
                'unit_price_cents' => $service->price_cents,
                'hourly_rate_cents' => $service->hourly_rate_cents,
                'estimated_minutes' => $service->estimated_minutes,
                'pricing_terms' => [
                    'description' => $this->pricing->describeTerms($service, $price->currency),
                    'quote_reasons' => $price->quoteReasons,
                ],
                'subtotal_cents' => $price->subtotalCents,
                'discount_cents' => $price->discountCents,
                'tax_cents' => $price->taxCents,
                'total_cents' => $price->totalCents,
                'tax_breakdown' => $price->taxes,
                'currency' => $price->currency,
                'quote_required' => $price->quoteRequired,
                'access_token_hash' => hash('sha256', $accessToken),
                'draft_token_hash' => null,
                'terms_accepted_at' => now(),
                'submitted_at' => now(),
            ])->save();

            $this->stateMachine->transition(
                $booking,
                $price->quoteRequired ? BookingStatus::PendingQuote : BookingStatus::AwaitingPayment,
                $user,
                'Submitted by customer',
            );

            $this->systemMessage($booking, $price->quoteRequired
                ? 'Booking submitted. Our team will review the requirements and send a quote.'
                : 'Booking submitted and ready for payment.');
        });

        DB::afterCommit(function () use ($booking, $accessToken): void {
            $this->notifier->customer($booking, new BookingReceivedNotification($booking, $this->notifier->tokenUrl($booking, $accessToken)));
            $this->notifier->admins(new NewBookingNotification($booking));
        });

        return $accessToken;
    }

    /**
     * Customer cancellation: unpaid bookings cancel immediately, paid bookings create a request.
     *
     * @throws BookingActionException
     */
    public function cancelByCustomer(Booking $booking, ?User $user, ?string $reason): BookingStatus
    {
        $reason = filled($reason) ? Str::limit(trim((string) $reason), 480) : null;

        // Re-read under a lock so a payment confirming at the same moment cannot be cancelled without the refund flow.
        $result = DB::transaction(function () use ($booking, $user, $reason): BookingStatus {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
            $booking->setRawAttributes($locked->getAttributes(), true);

            if ($locked->status->isUnpaidOpen()) {
                if ($locked->hasOpenPayment()) {
                    throw new BookingActionException('A payment is in progress for this booking. Please wait a moment and try again.');
                }

                $this->stateMachine->transition($booking, BookingStatus::Cancelled, $user, $reason ?? 'Cancelled by customer');

                return BookingStatus::Cancelled;
            }

            if ($locked->status !== BookingStatus::Confirmed) {
                throw new BookingActionException('This booking can no longer be cancelled online. Please contact us.');
            }

            if (! $this->isWithinCancellationWindow($locked)) {
                throw new BookingActionException('The online cancellation window for this booking has passed. Please contact us.');
            }

            $this->stateMachine->transition($booking, BookingStatus::CancellationRequested, $user, $reason ?? 'Requested by customer');

            return BookingStatus::CancellationRequested;
        });

        if ($result === BookingStatus::Cancelled) {
            $this->notifier->customer($booking, new BookingCancelledNotification($booking, null));
            $this->notifier->admins(new AdminBookingCancelledNotification($booking, null));
        } else {
            $this->notifier->admins(new CancellationRequestedNotification($booking));
        }

        return $result;
    }

    public function isWithinCancellationWindow(Booking $booking): bool
    {
        if ($booking->preferred_date === null) {
            return true;
        }

        $visitStart = $booking->scheduled_at ?? $booking->preferred_date->copy()->startOfDay();
        $cutoffHours = (int) app(SettingsService::class)->get('cancellation.cutoff_hours', 48);

        return now()->addHours($cutoffHours)->lt($visitStart);
    }

    public function canCustomerCancel(Booking $booking): bool
    {
        return $booking->status->isUnpaidOpen()
            || ($booking->status === BookingStatus::Confirmed && $this->isWithinCancellationWindow($booking));
    }

    /**
     * Create a follow-up booking (e.g. the next recurring visit) awaiting a new quote.
     */
    public function repeat(Booking $source, User $admin, string $preferredDate, string $timeWindow): Booking
    {
        return DB::transaction(function () use ($source, $admin, $preferredDate, $timeWindow): Booking {
            $source->loadMissing(['address', 'addons']);

            $copy = new Booking;
            $copy->forceFill([
                ...$source->only([
                    'user_id', 'service_id', 'service_name', 'pricing_method', 'unit_price_cents', 'hourly_rate_cents',
                    'estimated_minutes', 'pricing_terms', 'frequency', 'contact_name', 'contact_email', 'contact_phone',
                    'currency', 'chat_state',
                ]),
                'reference' => app(ReferenceGenerator::class)->generate(),
                'repeated_from_id' => $source->id,
                'status' => BookingStatus::PendingQuote,
                'preferred_date' => $preferredDate,
                'time_window' => $timeWindow,
                'quote_required' => true,
                'access_token_hash' => null,
                'terms_accepted_at' => $source->terms_accepted_at,
                'submitted_at' => now(),
            ])->save();

            if ($source->address !== null) {
                $copy->address()->create($source->address->only(['line1', 'line2', 'city', 'province', 'postal_code', 'country', 'notes']));
            }

            foreach ($source->addons as $addon) {
                $copy->addons()->create($addon->only([
                    'service_addon_id', 'name', 'description', 'pricing_method', 'unit_price_cents', 'quantity',
                    'line_total_cents', 'needs_quote', 'source',
                ]));
            }

            $copy->statusHistory()->create([
                'from_status' => null,
                'to_status' => BookingStatus::PendingQuote,
                'user_id' => $admin->getKey(),
                'reason' => "Repeat of {$source->reference}",
            ]);

            $this->systemMessage($copy, "Repeat booking created from {$source->reference}. Original requirements are in that booking.");

            return $copy;
        });
    }

    public function systemMessage(Booking $booking, string $body): void
    {
        $booking->messages()->create([
            'sender' => MessageSender::System,
            'type' => MessageType::Text,
            'body' => $body,
        ]);
    }
}
