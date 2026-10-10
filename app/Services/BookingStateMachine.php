<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidBookingTransition;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The only place a booking's status changes. Every transition is validated, locked and recorded.
 */
class BookingStateMachine
{
    /**
     * @throws InvalidBookingTransition
     */
    public function transition(Booking $booking, BookingStatus $to, ?User $actor = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $to, $actor, $reason): Booking {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InvalidBookingTransition::between($from, $to);
            }

            $locked->status = $to;

            match ($to) {
                BookingStatus::Confirmed => $locked->confirmed_at ??= now(),
                BookingStatus::Completed => $locked->completed_at = now(),
                BookingStatus::Cancelled => $locked->cancelled_at = now(),
                default => null,
            };

            if ($to === BookingStatus::Cancelled && $reason !== null) {
                $locked->cancellation_reason = $reason;
            }

            $locked->save();

            $locked->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor?->getKey(),
                'reason' => $reason,
            ]);

            $booking->setRawAttributes($locked->getAttributes(), true);

            return $booking;
        });
    }
}
