<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * Decides whether the current visitor may see a submitted booking.
 *
 * Access is granted to the owner, administrators, this browser session (after submitting
 * or opening a valid link), or anyone holding the secret access token from the email link.
 */
class BookingAccess
{
    public const SESSION_KEY = 'accessible_bookings';

    public function canView(Booking $booking, Request $request): bool
    {
        $user = $request->user();

        if ($user?->isAdmin() || $booking->isOwnedBy($user)) {
            return true;
        }

        if (in_array($booking->reference, (array) $request->session()->get(self::SESSION_KEY, []), true)) {
            return true;
        }

        if ($request->routeIs('bookings.show') && $request->query('signature') !== null && $request->hasValidSignature()) {
            $this->grant($booking, $request);

            return true;
        }

        $token = $request->query('token');

        if (is_string($token) && $token !== '' && $booking->access_token_hash !== null
            && hash_equals($booking->access_token_hash, hash('sha256', $token))) {
            $this->grant($booking, $request);

            return true;
        }

        return false;
    }

    /**
     * Remember that this browser session may view the booking.
     */
    public function grant(Booking $booking, Request $request): void
    {
        $references = (array) $request->session()->get(self::SESSION_KEY, []);

        if (! in_array($booking->reference, $references, true)) {
            $references[] = $booking->reference;
            $request->session()->put(self::SESSION_KEY, array_slice($references, -20));
        }
    }

    /**
     * Whether this visitor may change the booking (accept quotes, pay, cancel).
     * Administrators can view but customer actions require the customer.
     */
    public function canAct(Booking $booking, Request $request): bool
    {
        $user = $request->user();

        if ($booking->isOwnedBy($user)) {
            return true;
        }

        if ($booking->user_id !== null) {
            return false;
        }

        return ! ($user?->isAdmin() ?? false) && $this->canView($booking, $request);
    }
}
