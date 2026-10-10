<?php

namespace Tests\Feature\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\Admin\NewBookingNotification;
use App\Services\BookingNotifier;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_notifies_the_business_email_and_every_admin_user(): void
    {
        Notification::fake();
        app(SettingsService::class)->set(['business.email' => 'owner@scrubit.ca']);
        $admin = User::factory()->admin()->create(['email' => 'team@scrubit.ca']);
        $booking = Booking::factory()->create();

        app(BookingNotifier::class)->admins(new NewBookingNotification($booking));

        Notification::assertSentOnDemandTimes(NewBookingNotification::class, 1);
        Notification::assertSentTo($admin, NewBookingNotification::class);
        Notification::assertCount(2);
    }

    public function test_admins_does_not_duplicate_when_the_business_email_matches_an_admin(): void
    {
        Notification::fake();
        app(SettingsService::class)->set(['business.email' => 'Team@Scrubit.ca']);
        $admin = User::factory()->admin()->create(['email' => 'team@scrubit.ca']);
        $booking = Booking::factory()->create();

        app(BookingNotifier::class)->admins(new NewBookingNotification($booking));

        Notification::assertSentTo($admin, NewBookingNotification::class);
        Notification::assertCount(1);
    }
}
