<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Succeeded => 'Paid',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Succeeded, self::Failed, self::Cancelled],
            self::Processing => [self::Succeeded, self::Failed, self::Cancelled],
            self::Succeeded => [self::PartiallyRefunded, self::Refunded],
            self::PartiallyRefunded => [self::PartiallyRefunded, self::Refunded],
            self::Failed, self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }

    /**
     * Statuses where money was collected (revenue may be partially returned).
     *
     * @return list<self>
     */
    public static function collected(): array
    {
        return [self::Succeeded, self::PartiallyRefunded, self::Refunded];
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending, self::Processing => 'bg-sky-100 text-sky-800',
            self::Succeeded => 'bg-emerald-100 text-emerald-800',
            self::Failed => 'bg-red-100 text-red-800',
            self::Cancelled => 'bg-slate-100 text-slate-700',
            self::PartiallyRefunded, self::Refunded => 'bg-amber-100 text-amber-800',
        };
    }
}
