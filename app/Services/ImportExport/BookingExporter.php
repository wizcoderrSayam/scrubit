<?php

namespace App\Services\ImportExport;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Support\Money;

/**
 * Submitted bookings, optionally filtered by submission date and status.
 */
final class BookingExporter
{
    /**
     * @return iterable<int, list<string>>
     */
    public function rows(?string $from, ?string $to, ?BookingStatus $status): iterable
    {
        $bookings = Booking::query()
            ->with('address')
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status),
                fn ($query) => $query->submitted(),
            )
            ->when($from, fn ($query) => $query->whereDate('submitted_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('submitted_at', '<=', $to))
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        foreach ($bookings as $booking) {
            yield [
                $booking->reference,
                $booking->status->value,
                $booking->service_name,
                $booking->preferred_date?->toDateString() ?? '',
                $booking->time_window?->value ?? '',
                $booking->scheduled_at?->format('Y-m-d H:i:s') ?? '',
                Money::toDecimal($booking->subtotal_cents),
                Money::toDecimal($booking->discount_cents),
                Money::toDecimal($booking->tax_cents),
                Money::toDecimal($booking->total_cents),
                $booking->currency,
                $booking->contact_name ?? '',
                $booking->contact_email ?? '',
                $booking->contact_phone ?? '',
                $booking->address?->oneLine() ?? '',
            ];
        }
    }
}
