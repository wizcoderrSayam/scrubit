<?php

namespace Tests\Unit\Enums;

use App\Enums\BookingStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BookingStatusTest extends TestCase
{
    /**
     * @return array<string, array{BookingStatus, BookingStatus, bool}>
     */
    public static function transitions(): array
    {
        return [
            'draft to pending quote' => [BookingStatus::Draft, BookingStatus::PendingQuote, true],
            'draft to awaiting payment' => [BookingStatus::Draft, BookingStatus::AwaitingPayment, true],
            'draft straight to confirmed' => [BookingStatus::Draft, BookingStatus::Confirmed, false],
            'pending quote straight to confirmed' => [BookingStatus::PendingQuote, BookingStatus::Confirmed, false],
            'quote sent' => [BookingStatus::PendingQuote, BookingStatus::AwaitingQuotePayment, true],
            'quote paid' => [BookingStatus::AwaitingQuotePayment, BookingStatus::Confirmed, true],
            'quote expired' => [BookingStatus::AwaitingQuotePayment, BookingStatus::PendingQuote, true],
            'confirmed to in progress' => [BookingStatus::Confirmed, BookingStatus::InProgress, true],
            'in progress to completed' => [BookingStatus::InProgress, BookingStatus::Completed, true],
            'confirmed straight to completed' => [BookingStatus::Confirmed, BookingStatus::Completed, false],
            'cancellation requested' => [BookingStatus::Confirmed, BookingStatus::CancellationRequested, true],
            'cancellation declined' => [BookingStatus::CancellationRequested, BookingStatus::Confirmed, true],
            'completed is final' => [BookingStatus::Completed, BookingStatus::Cancelled, false],
            'cancelled is final' => [BookingStatus::Cancelled, BookingStatus::Confirmed, false],
            'in progress cannot be cancelled' => [BookingStatus::InProgress, BookingStatus::Cancelled, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_allows_only_defined_transitions(BookingStatus $from, BookingStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_only_awaiting_statuses_are_payable(): void
    {
        $payable = array_values(array_filter(BookingStatus::cases(), fn (BookingStatus $status) => $status->isPayable()));

        $this->assertSame([BookingStatus::AwaitingQuotePayment, BookingStatus::AwaitingPayment], $payable);
    }
}
