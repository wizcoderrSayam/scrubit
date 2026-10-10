<?php

namespace App\Services\Chat;

use App\Models\Booking;

/**
 * Rule-based, service-aware requirements capture. No external AI service is used.
 */
class ScriptedResponder implements ChatResponder
{
    public const TEXT_STEPS = ['needs'];

    public const MIN_DETAIL_LENGTH = 20;

    public function greeting(Booking $booking): string
    {
        return $booking->service->chatGreeting();
    }

    public function steps(Booking $booking): array
    {
        return ['needs', 'schedule', 'details', 'extras'];
    }

    public function promptFor(string $step, Booking $booking): string
    {
        return match ($step) {
            'needs' => $booking->service->chatNeedsPrompt()
                .' Also let us know the property type, number of bedrooms and bathrooms, approximate size, and its current condition.',
            'schedule' => 'When would you like the first visit? Choose a preferred date and time window'
                .($booking->service->allows_recurring ? ', and how often you would like us to come' : '')
                .'. We will confirm the exact arrival time.',
            'details' => 'Where should we clean, and how can we reach you about this booking? Please enter the service address and your contact details.',
            'extras' => 'Would you like any add-on services, or is there anything else we should know? Anything that needs a custom price is reviewed by our team before you pay.',
            default => 'That is everything we need. Review your booking whenever you are ready. You can keep adding details here at any time.',
        };
    }

    public function replyToText(string $currentStep, string $text, Booking $booking): array
    {
        if (in_array($currentStep, self::TEXT_STEPS, true)) {
            if (mb_strlen(trim($text)) < self::MIN_DETAIL_LENGTH) {
                return ['Could you share a little more detail? The more we know, the more accurate your quote will be.', false];
            }

            return [null, true];
        }

        if ($currentStep === 'done') {
            return ['Noted, I have added that to your requirements.', false];
        }

        return ['Noted, I have added that to your requirements. When you are ready, please complete the form below to continue.', false];
    }
}
