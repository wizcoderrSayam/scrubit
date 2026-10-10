<?php

namespace App\Listeners;

use App\Models\EmailLog;
use App\Notifications\TemplatedNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

/**
 * Records every templated email actually sent, so each booking shows its own email history in the admin.
 */
class LogSentEmail
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'mail' || ! $event->notification instanceof TemplatedNotification) {
            return;
        }

        $recipient = $this->recipient($event->notifiable);

        if ($recipient === '') {
            return;
        }

        // The email has already been sent; a logging failure here must never fail the job and trigger a resend.
        try {
            EmailLog::create([
                'booking_id' => $event->notification->loggedBooking()?->id,
                'recipient' => $recipient,
                'subject' => $event->notification->loggedSubject() ?? '',
                'template_key' => $event->notification->loggedTemplateKey(),
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * The mail address a notification was routed to, for a real User or an on-demand (anonymous) notifiable.
     */
    private function recipient(object $notifiable): string
    {
        $route = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('mail')
            : null;

        if (is_array($route)) {
            $first = array_key_first($route);

            return is_string($first) ? $first : (string) reset($route);
        }

        return (string) ($route ?? '');
    }
}
