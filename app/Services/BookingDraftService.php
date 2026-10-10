<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and resumes booking drafts. A guest draft is tied to a long-lived browser cookie.
 */
class BookingDraftService
{
    public const COOKIE = 'scrubit_draft';

    public const COOKIE_MINUTES = 60 * 24 * 60;

    public function __construct(
        private ReferenceGenerator $references,
        private ChatService $chat,
    ) {}

    public function startOrResume(Service $service, Request $request): Booking
    {
        $user = $request->user();
        $tokenHash = $this->browserTokenHash($request) ?? $this->issueBrowserToken();

        $existing = Booking::query()
            ->where('service_id', $service->id)
            ->where('status', BookingStatus::Draft)
            ->when(
                $user !== null,
                fn ($query) => $query->where('user_id', $user->getKey()),
                fn ($query) => $query->whereNull('user_id')->where('draft_token_hash', $tokenHash),
            )
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($service, $user, $tokenHash): Booking {
            $booking = new Booking;
            $booking->forceFill([
                'reference' => $this->references->generate(),
                'service_id' => $service->id,
                'user_id' => $user?->getKey(),
                'draft_token_hash' => $user === null ? $tokenHash : null,
                'status' => BookingStatus::Draft,
                'service_name' => $service->name,
                'pricing_method' => $service->pricing_method,
                'contact_name' => $user?->name,
                'contact_email' => $user?->email,
                'contact_phone' => $user?->phone,
            ])->save();

            $booking->setRelation('service', $service);
            $this->chat->start($booking);

            return $booking;
        });
    }

    /**
     * Whether the current browser or user may work on this draft.
     */
    public function canAccess(Booking $booking, Request $request): bool
    {
        if ($booking->status !== BookingStatus::Draft) {
            return false;
        }

        $user = $request->user();

        if ($booking->user_id !== null) {
            return $booking->isOwnedBy($user);
        }

        $tokenHash = $this->browserTokenHash($request);

        return $tokenHash !== null && $booking->draft_token_hash !== null && hash_equals($booking->draft_token_hash, $tokenHash);
    }

    /**
     * Attach this browser's guest drafts to a user who has just logged in or registered.
     */
    public function claimForUser(User $user, Request $request): void
    {
        $tokenHash = $this->browserTokenHash($request);

        if ($tokenHash === null) {
            return;
        }

        Booking::query()
            ->where('status', BookingStatus::Draft)
            ->whereNull('user_id')
            ->where('draft_token_hash', $tokenHash)
            ->update(['user_id' => $user->getKey(), 'draft_token_hash' => null]);
    }

    public function pruneStaleDrafts(int $days = 30): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Draft)
            ->where('updated_at', '<', now()->subDays($days))
            ->delete();
    }

    private function browserTokenHash(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return is_string($token) && strlen($token) === 40 ? hash('sha256', $token) : null;
    }

    private function issueBrowserToken(): string
    {
        $token = Str::random(40);
        Cookie::queue(Cookie::make(self::COOKIE, $token, self::COOKIE_MINUTES, httpOnly: true, sameSite: 'lax'));

        return hash('sha256', $token);
    }
}
