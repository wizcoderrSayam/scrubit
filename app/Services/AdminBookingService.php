<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\MessageSender;
use App\Enums\MessageType;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Customer\BookingCancelledNotification;
use App\Services\Payments\PaymentService;

/**
 * Administrator actions on bookings. Payment-driven transitions are not available here.
 */
class AdminBookingService
{
    /**
     * Statuses an administrator may set manually.
     */
    public const MANUAL_TARGETS = [
        BookingStatus::InProgress,
        BookingStatus::Completed,
        BookingStatus::Cancelled,
        BookingStatus::Confirmed,
    ];

    public function __construct(
        private BookingStateMachine $stateMachine,
        private PaymentService $payments,
        private BookingNotifier $notifier,
    ) {}

    /**
     * @return list<BookingStatus>
     */
    public function manualTransitions(Booking $booking): array
    {
        return array_values(array_filter(
            self::MANUAL_TARGETS,
            fn (BookingStatus $to) => $booking->status->canTransitionTo($to)
                && ($to !== BookingStatus::Confirmed || $booking->status === BookingStatus::CancellationRequested),
        ));
    }

    /**
     * @throws BookingActionException
     */
    public function transition(Booking $booking, BookingStatus $to, User $admin, ?string $reason, int $refundCents = 0): void
    {
        if (! in_array($to, $this->manualTransitions($booking), true)) {
            throw new BookingActionException("This booking cannot be moved to {$to->label()} manually.");
        }

        if ($to === BookingStatus::Cancelled && $booking->hasOpenPayment()) {
            throw new BookingActionException('A payment is in progress. Wait for it to finish before cancelling.');
        }

        $payment = $booking->successfulPayment;

        if ($refundCents > 0 && ($to !== BookingStatus::Cancelled || $payment === null)) {
            throw new BookingActionException('Refunds can only be issued when cancelling a paid booking.');
        }

        // The refund talks to the payment provider, so it runs before (not inside) the status transaction.
        if ($refundCents > 0 && $payment !== null) {
            $this->payments->refund($payment, $refundCents, $admin);
        }

        $label = match ($to) {
            BookingStatus::Confirmed => 'Cancellation request declined',
            default => 'Updated by admin',
        };

        $this->stateMachine->transition($booking, $to, $admin, $reason ?: $label);

        if ($to === BookingStatus::Cancelled) {
            $this->notifier->customer($booking, new BookingCancelledNotification($booking, $refundCents > 0 ? $refundCents : null));
        }
    }

    public function addNote(Booking $booking, User $admin, string $note): void
    {
        $booking->messages()->create([
            'sender' => MessageSender::Admin,
            'type' => MessageType::InternalNote,
            'body' => $note,
            'user_id' => $admin->getKey(),
        ]);
    }
}
