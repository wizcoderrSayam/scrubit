<?php

namespace App\Services\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\Admin\PaymentNeedsAttentionNotification;
use App\Notifications\Admin\PaymentReceivedNotification;
use App\Notifications\Customer\BookingConfirmedNotification;
use App\Services\BookingNotifier;
use App\Services\BookingStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Creates checkout attempts and applies verified gateway events.
 *
 * A booking is only ever confirmed here, from a verified event, never from a return URL.
 */
class PaymentService
{
    public const PROCESSING_TIMEOUT_HOURS = 24;

    public function __construct(
        private PaymentGatewayManager $gateways,
        private BookingStateMachine $stateMachine,
        private BookingNotifier $notifier,
    ) {}

    /**
     * Start (or resume) checkout for the booking's currently payable amount.
     *
     * @throws BookingActionException
     */
    public function startCheckout(Booking $booking): Payment
    {
        $gateway = $this->gateways->driver();

        $payment = DB::transaction(function () use ($booking, $gateway): Payment {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
            $amount = $locked->payableAmountCents();
            $quote = $locked->status === BookingStatus::AwaitingQuotePayment ? $locked->currentQuote : null;

            if ($amount === null) {
                throw new BookingActionException('This booking has nothing to pay right now.');
            }

            if ($quote !== null && $quote->status !== QuoteStatus::Accepted) {
                throw new BookingActionException('Please accept the quote before paying.');
            }

            $openPayments = $locked->payments()->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])->lockForUpdate()->get();

            foreach ($openPayments as $open) {
                if ($open->status === PaymentStatus::Processing) {
                    throw new BookingActionException('Your previous payment is still being processed. Please wait a moment.');
                }

                $reusable = $open->checkout_url !== null
                    && $open->checkout_expires_at?->isFuture()
                    && $open->amount_cents === $amount
                    && $open->booking_quote_id === $quote?->id
                    && $open->provider === $gateway->name();

                if ($reusable) {
                    return $open;
                }

                $open->forceFill(['status' => PaymentStatus::Cancelled, 'failure_reason' => 'Superseded by a new checkout'])->save();
            }

            return $locked->payments()->create([
                'booking_quote_id' => $quote?->id,
                'provider' => $gateway->name(),
                'idempotency_key' => (string) Str::uuid(),
                'amount_cents' => $amount,
                'currency' => $quote->currency ?? $locked->currency,
                'status' => PaymentStatus::Pending,
            ]);
        });

        if ($payment->checkout_url !== null) {
            return $payment;
        }

        try {
            $session = $gateway->createCheckout(
                $payment,
                route('checkout.return', ['booking' => $booking->reference, 'payment' => $payment->idempotency_key]),
                route('checkout.cancel', ['booking' => $booking->reference, 'payment' => $payment->idempotency_key]),
            );
        } catch (Throwable $exception) {
            $payment->forceFill(['status' => PaymentStatus::Failed, 'failure_reason' => 'Could not start checkout'])->save();
            Log::error('Checkout creation failed', ['payment_id' => $payment->id, 'exception' => $exception->getMessage()]);

            throw new BookingActionException('We could not start the payment. Please try again in a moment.');
        }

        $expiresAt = $session->expiresAt;

        if ($payment->quote?->expires_at !== null && $payment->quote->expires_at->lt($expiresAt)) {
            $expiresAt = $payment->quote->expires_at;
        }

        $payment->forceFill([
            'provider_ref' => $session->providerRef,
            'checkout_url' => $session->url,
            'checkout_expires_at' => $expiresAt,
        ])->save();

        return $payment;
    }

    /**
     * Apply a verified gateway event. Safe to call more than once for the same event.
     */
    public function handleEvent(GatewayEvent $event): void
    {
        $bookingId = Payment::query()
            ->where('provider', $event->provider)
            ->where('provider_ref', $event->providerRef)
            ->value('booking_id');

        if ($bookingId === null) {
            Log::warning('Payment event for unknown payment', ['provider' => $event->provider, 'event_id' => $event->eventId]);

            throw new UnknownPaymentException("No payment matches provider reference for event {$event->eventId}.");
        }

        $afterCommit = DB::transaction(function () use ($event, $bookingId): ?\Closure {
            // Lock order matches startCheckout(): booking first, then payment.
            /** @var Booking $booking */
            $booking = Booking::query()->lockForUpdate()->findOrFail($bookingId);

            /** @var Payment $payment */
            $payment = Payment::query()
                ->where('provider', $event->provider)
                ->where('provider_ref', $event->providerRef)
                ->lockForUpdate()
                ->firstOrFail();

            return match ($event->type) {
                GatewayEventType::Processing => $this->moveTo($payment, PaymentStatus::Processing),
                GatewayEventType::Failed => $this->moveTo($payment, PaymentStatus::Failed, $event->failureReason ?? 'Payment failed'),
                GatewayEventType::Cancelled => $this->moveTo($payment, PaymentStatus::Cancelled, 'Checkout cancelled'),
                GatewayEventType::Succeeded => $this->applySuccess($payment, $booking, $event),
            };
        });

        if ($afterCommit !== null) {
            DB::afterCommit($afterCommit);
        }
    }

    /**
     * @throws BookingActionException
     */
    public function refund(Payment $payment, int $amountCents, User $admin): Payment
    {
        return DB::transaction(function () use ($payment, $amountCents, $admin): Payment {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($amountCents <= 0 || $amountCents > $locked->refundableCents()) {
                throw new BookingActionException('The refund amount must be between $0.01 and the remaining paid amount.');
            }

            $this->gateways->driver($locked->provider)->refund($locked, $amountCents);

            $refunded = $locked->refunded_cents + $amountCents;
            $locked->forceFill([
                'refunded_cents' => $refunded,
                'status' => $refunded >= $locked->amount_cents ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
                'needs_attention' => $refunded >= $locked->amount_cents ? false : $locked->needs_attention,
                'meta' => [...(array) $locked->meta, 'refunds' => [
                    ...(array) ($locked->meta['refunds'] ?? []),
                    ['amount_cents' => $amountCents, 'by' => $admin->getKey(), 'at' => now()->toIso8601String()],
                ]],
            ])->save();

            $payment->setRawAttributes($locked->getAttributes(), true);

            return $payment;
        });
    }

    /**
     * Mark abandoned checkouts as cancelled so the customer can start again cleanly.
     */
    public function expireStaleCheckouts(): int
    {
        $expired = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('checkout_expires_at', '<', now()->subMinutes(5))
            ->update(['status' => PaymentStatus::Cancelled, 'failure_reason' => 'Checkout session expired', 'updated_at' => now()]);

        // A payment stuck in "processing" would block the booking forever; release it and flag it for review.
        $stuck = Payment::query()
            ->where('status', PaymentStatus::Processing)
            ->where('updated_at', '<', now()->subHours(self::PROCESSING_TIMEOUT_HOURS))
            ->update([
                'status' => PaymentStatus::Failed,
                'failure_reason' => 'No confirmation received from the payment provider',
                'needs_attention' => true,
                'updated_at' => now(),
            ]);

        return $expired + $stuck;
    }

    public function resolveAttention(Payment $payment): void
    {
        $payment->forceFill(['needs_attention' => false])->save();
    }

    private function moveTo(Payment $payment, PaymentStatus $to, ?string $reason = null): null
    {
        if ($payment->status->canTransitionTo($to)) {
            $payment->forceFill(['status' => $to, 'failure_reason' => $reason ?? $payment->failure_reason])->save();
        }

        return null;
    }

    private function applySuccess(Payment $payment, Booking $booking, GatewayEvent $event): ?\Closure
    {
        if (in_array($payment->status, PaymentStatus::collected(), true)) {
            return null;
        }

        $previousStatus = $payment->status;
        $payment->forceFill(['status' => PaymentStatus::Succeeded, 'paid_at' => now()]);

        if ($event->amountCents !== null && $event->amountCents !== $payment->amount_cents) {
            return $this->flagForAttention($payment, $booking, "Provider reported {$event->amountCents} cents but {$payment->amount_cents} was expected.");
        }

        if (! $this->canConfirm($payment, $booking)) {
            return $this->flagForAttention($payment, $booking, $previousStatus->isOpen()
                ? "Payment succeeded while the booking was {$booking->status->label()}."
                : "Payment succeeded after it was marked {$previousStatus->label()}.");
        }

        $payment->forceFill(['success_guard' => $booking->id])->save();

        if ($payment->quote !== null && $payment->quote->status === QuoteStatus::Sent) {
            $payment->quote->forceFill(['status' => QuoteStatus::Accepted, 'accepted_at' => now()])->save();
        }

        $this->stateMachine->transition($booking, BookingStatus::Confirmed, null, 'Payment confirmed by '.$payment->provider);

        return function () use ($booking, $payment): void {
            $this->notifier->customer($booking, new BookingConfirmedNotification($booking, $payment, $this->notifier->customerUrl($booking)));
            $this->notifier->admins(new PaymentReceivedNotification($booking, $payment));
        };
    }

    private function canConfirm(Payment $payment, Booking $booking): bool
    {
        if (! $booking->status->isPayable() || $booking->successfulPayment()->exists()) {
            return false;
        }

        if ($booking->status === BookingStatus::AwaitingPayment) {
            return $payment->booking_quote_id === null && $payment->amount_cents === $booking->total_cents;
        }

        $quote = $payment->quote;

        return $quote !== null
            && $quote->status->isCurrent()
            && ! $quote->isExpired()
            && $quote->id === $booking->currentQuote?->id
            && $payment->amount_cents === $quote->total_cents;
    }

    private function flagForAttention(Payment $payment, Booking $booking, string $reason): \Closure
    {
        $payment->forceFill([
            'needs_attention' => true,
            'meta' => [...(array) $payment->meta, 'needs_refund' => true, 'attention_reason' => $reason],
        ])->save();

        return fn () => $this->notifier->admins(new PaymentNeedsAttentionNotification($booking, $payment, $reason));
    }
}
