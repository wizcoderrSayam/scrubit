<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Draft = 'draft';
    case PendingQuote = 'pending_quote';
    case AwaitingQuotePayment = 'awaiting_quote_payment';
    case AwaitingPayment = 'awaiting_payment';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case CancellationRequested = 'cancellation_requested';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingQuote => 'Pending quote',
            self::AwaitingQuotePayment => 'Awaiting quote payment',
            self::AwaitingPayment => 'Awaiting payment',
            self::Confirmed => 'Confirmed',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::CancellationRequested => 'Cancellation requested',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingQuote, self::AwaitingPayment],
            self::PendingQuote => [self::AwaitingQuotePayment, self::Cancelled],
            self::AwaitingQuotePayment => [self::Confirmed, self::PendingQuote, self::Cancelled],
            self::AwaitingPayment => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProgress, self::CancellationRequested, self::Cancelled],
            self::CancellationRequested => [self::Cancelled, self::Confirmed],
            self::InProgress => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::AwaitingPayment, self::AwaitingQuotePayment], true);
    }

    public function isUnpaidOpen(): bool
    {
        return in_array($this, [self::PendingQuote, self::AwaitingPayment, self::AwaitingQuotePayment], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /**
     * Tailwind classes for status badges.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700',
            self::PendingQuote, self::CancellationRequested => 'bg-amber-100 text-amber-800',
            self::AwaitingQuotePayment, self::AwaitingPayment => 'bg-sky-100 text-sky-800',
            self::Confirmed, self::InProgress => 'bg-brand-100 text-brand-800',
            self::Completed => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }
}
