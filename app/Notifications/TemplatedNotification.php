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

    /**
     * Set by toMail() so the email log listener can record the exact subject that was sent,
     * without re-rendering the template (and without guessing at a property name per subclass).
     */
    private ?string $renderedSubject = null;

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
        $this->renderedSubject = $rendered['subject'];

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view('emails.template', [
                'body' => $rendered['html'],
                'actionUrl' => $this->actionUrl(),
                'actionText' => $this->actionText(),
            ]);
    }

    /**
     * The booking this email relates to, for every notification in this app. Used only to log the email.
     */
    public function loggedBooking(): ?Booking
    {
        // Not every TemplatedNotification is about a booking (e.g. NewEnquiryNotification), so this property may not exist.
        return property_exists($this, 'booking') && $this->booking instanceof Booking ? $this->booking : null;
    }

    public function loggedTemplateKey(): string
    {
        return $this->templateKey();
    }

    /**
     * The subject actually sent. Only available after toMail() has run (i.e. from a NotificationSent listener).
     */
    public function loggedSubject(): ?string
    {
        return $this->renderedSubject;
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
