<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\EmailTemplateRenderer;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * A queued email rendered from an admin-editable template.
 */
abstract class TemplatedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    abstract protected function templateKey(): string;

    /**
     * @return array<string, string|null>
     */
    abstract protected function values(): array;

    protected function actionUrl(): ?string
    {
        return null;
    }

    protected function actionText(): string
    {
        return 'View booking';
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rendered = app(EmailTemplateRenderer::class)->render($this->templateKey(), $this->values());

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('emails.template', [
                'body' => $rendered['html'],
                'actionUrl' => $this->actionUrl(),
                'actionText' => $this->actionText(),
            ]);
    }

    /**
     * Placeholder values shared by booking notifications.
     *
     * @return array<string, string|null>
     */
    protected function bookingValues(Booking $booking): array
    {
        $visit = $booking->preferred_date
            ? $booking->preferred_date->format('l, F j, Y').($booking->time_window ? ', '.$booking->time_window->label() : '')
            : 'To be confirmed';

        return [
            'reference' => $booking->reference,
            'service' => $booking->service_name,
            'customer_name' => $booking->contact_name,
            'customer_email' => $booking->contact_email,
            'status' => $booking->status->label(),
            'visit' => $visit,
            'amount' => Money::format((int) $booking->total_cents, (string) $booking->currency),
        ];
    }
}
