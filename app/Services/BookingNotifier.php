<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\URL;

/**
 * Routes booking notifications to the right customer address and to administrators.
 */
class BookingNotifier
{
    public const LINK_DAYS = 60;

    public function __construct(private SettingsService $settings) {}

    public function customer(Booking $booking, Notification $notification): void
    {
        // Callers pass bookings loaded in many different ways; load the relation explicitly
        // rather than relying on it already being eager-loaded (loadMissing is a no-op if it is).
        $booking->loadMissing('user');

        if ($booking->user !== null) {
            $booking->user->notify($notification);

            return;
        }

        if (filled($booking->contact_email)) {
            NotificationFacade::route('mail', [$booking->contact_email => $booking->contact_name])->notify($notification);
        }
    }

    /**
     * Notify the business email plus every admin account, each address exactly once.
     */
    public function admins(Notification $notification): void
    {
        $admins = User::query()->admins()->get();

        $businessEmail = trim((string) $this->settings->get('business.email'));
        $adminEmails = $admins->pluck('email')->filter()->map(fn (string $email) => strtolower(trim($email)));

        $alreadyCovered = $businessEmail !== '' && $adminEmails->contains(strtolower($businessEmail));

        if ($businessEmail !== '' && ! $alreadyCovered) {
            NotificationFacade::route('mail', $businessEmail)->notify($notification);
        }

        if ($admins->isNotEmpty()) {
            NotificationFacade::send($admins, $notification);
        }
    }

    /**
     * A link the customer can open without logging in.
     */
    public function customerUrl(Booking $booking): string
    {
        return URL::temporarySignedRoute('bookings.show', now()->addDays(self::LINK_DAYS), ['booking' => $booking->reference]);
    }

    public function tokenUrl(Booking $booking, string $accessToken): string
    {
        return route('bookings.show', ['booking' => $booking->reference, 'token' => $accessToken]);
    }
}
