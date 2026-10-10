<?php

namespace App\Services\Chat;

use App\Models\Booking;

/**
 * Produces the assistant side of the customization conversation.
 *
 * The default implementation is a deterministic script. An AI-backed responder can
 * implement this contract later without changing the controllers or the UI.
 */
interface ChatResponder
{
    public function greeting(Booking $booking): string;

    /**
     * The ordered requirement steps for this booking.
     *
     * @return list<string>
     */
    public function steps(Booking $booking): array;

    /**
     * The assistant message that asks for the given step.
     */
    public function promptFor(string $step, Booking $booking): string;

    /**
     * Reply to a free-text customer message. Returns [reply, stepCompleted].
     *
     * @return array{0: string|null, 1: bool}
     */
    public function replyToText(string $currentStep, string $text, Booking $booking): array;
}
