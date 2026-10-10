<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Support\Markdown;
use Illuminate\Support\HtmlString;

/**
 * Renders admin-editable email templates. Placeholder values are always HTML-escaped.
 */
class EmailTemplateRenderer
{
    /**
     * Default templates, also used to seed the email_templates table.
     *
     * @var array<string, array{name: string, subject: string, body: string}>
     */
    public const DEFAULTS = [
        'customer.booking_received_quote' => [
            'name' => 'Customer: booking received (quote required)',
            'subject' => 'We received your booking {{reference}}',
            'body' => "Hi {{customer_name}},\n\nThank you for choosing {{business_name}}. We have received your **{{service}}** request (reference **{{reference}}**).\n\nOur team will review your requirements and send you a quote. You will only be asked to pay after you approve the price.",
        ],
        'customer.booking_received_payment' => [
            'name' => 'Customer: booking received (ready for payment)',
            'subject' => 'Your booking {{reference}} is ready for payment',
            'body' => "Hi {{customer_name}},\n\nThank you for choosing {{business_name}}. Your **{{service}}** booking (reference **{{reference}}**) is ready for payment.\n\nAmount due: **{{amount}}**.\n\nYour booking is confirmed once payment has been received.",
        ],
        'customer.quote_sent' => [
            'name' => 'Customer: quote ready',
            'subject' => 'Your quote for {{service}} ({{reference}})',
            'body' => "Hi {{customer_name}},\n\nYour quote for **{{service}}** is ready.\n\n**Total: {{amount}}** (including tax)\n\n{{quote_description}}\n\nThis quote is valid until {{expires_at}}. Approve it and pay securely using the button below.",
        ],
        'customer.booking_confirmed' => [
            'name' => 'Customer: booking confirmed',
            'subject' => 'Booking confirmed: {{reference}}',
            'body' => "Hi {{customer_name}},\n\nYour payment of **{{amount}}** has been received and your **{{service}}** booking is confirmed.\n\nPreferred visit: {{visit}}\n\nWe will contact you to confirm the exact arrival time. If you have any questions, reply to this email or call {{business_phone}}.",
        ],
        'customer.booking_cancelled' => [
            'name' => 'Customer: booking cancelled',
            'subject' => 'Booking {{reference}} cancelled',
            'body' => "Hi {{customer_name}},\n\nYour **{{service}}** booking (reference **{{reference}}**) has been cancelled.\n\n{{refund_note}}\n\nIf this was a mistake, please contact us at {{business_phone}}.",
        ],
        'admin.new_booking' => [
            'name' => 'Admin: new booking',
            'subject' => 'New booking {{reference}}: {{service}}',
            'body' => "A new booking has been submitted.\n\n- Reference: **{{reference}}**\n- Service: {{service}}\n- Customer: {{customer_name}} ({{customer_email}})\n- Status: {{status}}\n- Preferred visit: {{visit}}",
        ],
        'admin.payment_received' => [
            'name' => 'Admin: payment received',
            'subject' => 'Payment received for {{reference}}',
            'body' => "A payment of **{{amount}}** was confirmed for booking **{{reference}}** ({{service}}).\n\nCustomer: {{customer_name}} ({{customer_email}})",
        ],
        'admin.payment_attention' => [
            'name' => 'Admin: payment needs attention',
            'subject' => 'Payment needs attention: {{reference}}',
            'body' => "A payment of **{{amount}}** for booking **{{reference}}** needs review.\n\nReason: {{reason}}\n\nThe booking status was not changed. Review the payment and refund it if appropriate.",
        ],
        'admin.cancellation_requested' => [
            'name' => 'Admin: cancellation requested',
            'subject' => 'Cancellation requested for {{reference}}',
            'body' => "{{customer_name}} has requested cancellation of booking **{{reference}}** ({{service}}).\n\nPreferred visit: {{visit}}\n\nApprove or reject the request from the booking page.",
        ],
        'admin.new_enquiry' => [
            'name' => 'Admin: new enquiry',
            'subject' => 'New {{enquiry_type}} from {{customer_name}}',
            'body' => "You have a new {{enquiry_type}}.\n\n- Name: {{customer_name}}\n- Email: {{customer_email}}\n- Phone: {{customer_phone}}\n- Service: {{service}}\n\n{{message}}",
        ],
    ];

    public function __construct(private SettingsService $settings) {}

    /**
     * @param  array<string, string|null>  $values
     * @return array{subject: string, html: HtmlString}
     */
    public function render(string $key, array $values): array
    {
        $template = EmailTemplate::query()->where('key', $key)->first();
        $subject = $template->subject ?? self::DEFAULTS[$key]['subject'] ?? $key;
        $body = $template->body ?? self::DEFAULTS[$key]['body'] ?? '';

        $values = [
            'business_name' => (string) $this->settings->get('business.name'),
            'business_phone' => (string) $this->settings->get('business.phone'),
            'business_email' => (string) $this->settings->get('business.email'),
            ...$values,
        ];

        $html = (string) Markdown::render($body);

        foreach ($values as $name => $value) {
            $html = str_replace('{{'.$name.'}}', nl2br(e((string) $value)), $html);
            $subject = str_replace('{{'.$name.'}}', str_replace(["\r", "\n"], ' ', (string) $value), $subject);
        }

        return ['subject' => $subject, 'html' => new HtmlString($html)];
    }
}
