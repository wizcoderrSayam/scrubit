<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\BookingActionException;
use App\Models\Booking;
use App\Models\BookingQuote;
use App\Models\User;
use App\Notifications\Admin\QuoteAcceptedNotification;
use App\Notifications\Admin\QuoteExpiredNotification as AdminQuoteExpiredNotification;
use App\Notifications\Admin\QuoteSentCopyNotification;
use App\Notifications\Admin\RequoteRequestedNotification;
use App\Notifications\Customer\QuoteExpiredNotification;
use App\Notifications\Customer\QuoteSentNotification;
use App\Services\Pricing\BookingPricingService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Administrator-approved pricing for bookings that cannot be priced automatically.
 */
class QuoteService
{
    public function __construct(
        private BookingStateMachine $stateMachine,
        private BookingPricingService $pricing,
        private BookingNotifier $notifier,
        private SettingsService $settings,
    ) {}

    /**
     * Issue a quote to the customer, replacing any previous quote.
     *
     * @throws BookingActionException
     */
    public function send(Booking $booking, int $subtotalCents, string $description, CarbonInterface $expiresAt, User $admin): BookingQuote
    {
        if ($subtotalCents <= 0) {
            throw new BookingActionException('The quote amount must be greater than zero.');
        }

        if ($expiresAt->isPast()) {
            throw new BookingActionException('The quote expiry must be in the future.');
        }

        $quote = DB::transaction(function () use ($booking, $subtotalCents, $description, $expiresAt, $admin): BookingQuote {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if (! in_array($locked->status, [BookingStatus::PendingQuote, BookingStatus::AwaitingQuotePayment], true)) {
                throw new BookingActionException('Quotes can only be sent for bookings that are waiting for a quote.');
            }

            if ($locked->hasOpenPayment()) {
                throw new BookingActionException('The customer has a payment in progress. Wait for it to finish before sending a new quote.');
            }

            $locked->quotes()
                ->whereIn('status', [QuoteStatus::Draft, QuoteStatus::Sent, QuoteStatus::Accepted, QuoteStatus::Expired])
                ->update(['status' => QuoteStatus::Superseded]);

            $taxes = $this->pricing->taxesFor($subtotalCents);
            $taxCents = array_sum(array_column($taxes, 'amount_cents'));

            $quote = $locked->quotes()->create([
                'subtotal_cents' => $subtotalCents,
                'tax_cents' => $taxCents,
                'total_cents' => $subtotalCents + $taxCents,
                'tax_breakdown' => $taxes,
                'currency' => (string) $this->settings->get('business.currency', 'CAD'),
                'description' => $description,
                'status' => QuoteStatus::Sent,
                'expires_at' => $expiresAt,
                'sent_at' => now(),
                'created_by' => $admin->getKey(),
            ]);

            if ($locked->status === BookingStatus::PendingQuote) {
                $this->stateMachine->transition($locked, BookingStatus::AwaitingQuotePayment, $admin, 'Quote sent');
            }

            $booking->setRawAttributes($locked->getAttributes(), true);

            return $quote;
        });

        $booking->unsetRelation('currentQuote');
        $this->notifier->customer($booking, new QuoteSentNotification($booking, $quote, $this->notifier->customerUrl($booking)));
        $this->notifier->admins(new QuoteSentCopyNotification($booking, $quote));

        return $quote;
    }

    /**
     * The customer accepts the current quote, which allows checkout to start.
     *
     * @throws BookingActionException
     */
    public function accept(Booking $booking): BookingQuote
    {
        $justAccepted = false;

        $quote = DB::transaction(function () use ($booking, &$justAccepted): BookingQuote {
            $quote = $booking->currentQuote()->lockForUpdate()->first();

            if ($booking->status !== BookingStatus::AwaitingQuotePayment || $quote === null) {
                throw new BookingActionException('There is no quote waiting for your approval.');
            }

            if ($quote->isExpired()) {
                throw new BookingActionException('This quote has expired. Please request a new quote.');
            }

            if ($quote->status === QuoteStatus::Sent) {
                $quote->forceFill(['status' => QuoteStatus::Accepted, 'accepted_at' => now()])->save();
                $justAccepted = true;
            }

            return $quote;
        });

        // Only notify on the real transition, not on a repeat "accept & pay" click (e.g. after a retry).
        if ($justAccepted) {
            $this->notifier->admins(new QuoteAcceptedNotification($booking, $quote));
        }

        return $quote;
    }

    /**
     * Expire overdue quotes and return their bookings to the quote queue.
     * Bookings with a payment in progress are skipped so a late payment is never orphaned.
     */
    public function expireDue(): int
    {
        $expired = 0;

        BookingQuote::query()
            ->whereIn('status', [QuoteStatus::Sent, QuoteStatus::Accepted])
            ->where('expires_at', '<=', now())
            ->with('booking.user')
            ->chunkById(100, function ($quotes) use (&$expired): void {
                foreach ($quotes as $quote) {
                    $didExpire = DB::transaction(function () use ($quote): bool {
                        // Re-lock and re-check: a payment may have started checkout since this quote was selected above.
                        $locked = Booking::query()->lockForUpdate()->find($quote->booking_id);

                        if ($locked === null || $locked->hasOpenPayment()) {
                            return false;
                        }

                        $lockedQuote = BookingQuote::query()->lockForUpdate()->find($quote->id);

                        if ($lockedQuote === null || ! in_array($lockedQuote->status, [QuoteStatus::Sent, QuoteStatus::Accepted], true)) {
                            return false;
                        }

                        $lockedQuote->forceFill(['status' => QuoteStatus::Expired])->save();

                        if ($locked->status === BookingStatus::AwaitingQuotePayment) {
                            $this->stateMachine->transition($locked, BookingStatus::PendingQuote, null, 'Quote expired');
                        }

                        return true;
                    });

                    if (! $didExpire) {
                        continue;
                    }

                    $quote->booking->refresh();
                    $this->notifier->customer($quote->booking, new QuoteExpiredNotification($quote->booking, $this->notifier->customerUrl($quote->booking)));
                    $this->notifier->admins(new AdminQuoteExpiredNotification($quote->booking));

                    $expired++;
                }
            });

        return $expired;
    }

    /**
     * The customer asks for a fresh quote after the previous one expired.
     *
     * @throws BookingActionException
     */
    public function requestRequote(Booking $booking): void
    {
        $quote = $booking->quotes()->first();

        if ($booking->status !== BookingStatus::AwaitingQuotePayment || $quote === null || ! $quote->isExpired()) {
            throw new BookingActionException('A new quote can only be requested once the current quote has expired.');
        }

        if ($booking->hasOpenPayment()) {
            throw new BookingActionException('A payment is still being processed for this quote. Please wait a moment.');
        }

        DB::transaction(function () use ($booking, $quote): void {
            $quote->forceFill(['status' => QuoteStatus::Expired])->save();
            $this->stateMachine->transition($booking, BookingStatus::PendingQuote, $booking->user, 'Customer requested a new quote');
        });

        $this->notifier->admins(new RequoteRequestedNotification($booking));
    }

    public function defaultExpiry(): CarbonInterface
    {
        return now()->addDays((int) $this->settings->get('quote.default_expiry_days', 7))->endOfDay();
    }
}
