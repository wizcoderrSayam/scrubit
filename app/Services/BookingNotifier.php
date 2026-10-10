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
        if ($booking->user !== null) {
            $booking->user->notify($notification);

            return;
        }

        if (filled($booking->contact_email)) {
            NotificationFacade::route('mail', [$booking->contact_email => $booking->contact_name])->notify($notification);
        }
    }

    public function admins(Notification $notification): void
    {
        $admins = User::query()->admins()->get();

        if ($admins->isNotEmpty()) {
            NotificationFacade::send($admins, $notification);

            return;
        }

        NotificationFacade::route('mail', (string) $this->settings->get('business.email'))->notify($notification);
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
