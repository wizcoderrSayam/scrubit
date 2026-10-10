<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\BookingDraftService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;

/**
 * Moves drafts started as a guest in this browser onto the account that just signed in.
 */
class ClaimGuestDrafts
{
    public function __construct(private BookingDraftService $drafts) {}

    public function handle(Login|Registered $event): void
    {
        if ($event->user instanceof User && app()->bound('request')) {
            $this->drafts->claimForUser($event->user, request());
        }
    }
}
