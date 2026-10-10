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
        'customer.quote_expired' => [
            'name' => 'Customer: quote expired',
            'subject' => 'Your quote for {{service}} ({{reference}}) has expired',
            'body' => "Hi {{customer_name}},\n\nYour quote for **{{service}}** (reference **{{reference}}**) has expired and has not been paid.\n\nIf you would still like to go ahead, you can request a new quote from your booking page.",
        ],
        'customer.payment_failed' => [
            'name' => 'Customer: payment failed',
            'subject' => 'Payment did not go through: {{reference}}',
            'body' => "Hi {{customer_name}},\n\nYour payment for **{{service}}** (reference **{{reference}}**) did not go through.\n\nReason: {{failure_reason}}\n\nNo money was taken. You can try again from your booking page.",
        ],
        'customer.visit_scheduled' => [
            'name' => 'Customer: arrival time confirmed',
            'subject' => 'Your visit time is confirmed: {{reference}}',
            'body' => "Hi {{customer_name}},\n\nWe have confirmed the arrival time for your **{{service}}** booking (reference **{{reference}}**).\n\n**Arrival time: {{scheduled_at}}**\n\nIf this time no longer works for you, reply to this email or call {{business_phone}}.",
        ],
        'customer.booking_completed' => [
            'name' => 'Customer: booking completed',
            'subject' => 'Thank you! {{service}} completed ({{reference}})',
            'body' => "Hi {{customer_name}},\n\nYour **{{service}}** booking (reference **{{reference}}**) is now complete. Thank you for choosing {{business_name}}!\n\nWe would love to help again. If you have any feedback, just reply to this email.",
        ],
        'customer.cancellation_declined' => [
            'name' => 'Customer: cancellation request declined',
            'subject' => 'Your cancellation request was not approved: {{reference}}',
            'body' => "Hi {{customer_name}},\n\nWe were not able to approve your cancellation request for booking **{{reference}}** ({{service}}). Your booking remains confirmed.\n\nIf you have any questions, reply to this email or call {{business_phone}}.",
        ],
        'customer.refund_issued' => [
            'name' => 'Customer: refund issued',
            'subject' => 'Refund issued: {{reference}}',
            'body' => "Hi {{customer_name}},\n\nA refund of **{{amount}}** has been issued for your **{{service}}** booking (reference **{{reference}}**). It should appear on your original payment method within a few business days.\n\nIf you have any questions, reply to this email or call {{business_phone}}.",
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
        'admin.quote_sent' => [
            'name' => 'Admin: quote sent (copy)',
            'subject' => 'Quote sent for {{reference}}: {{amount}}',
            'body' => "A quote was sent to the customer.\n\n- Reference: **{{reference}}**\n- Service: {{service}}\n- Amount: **{{amount}}**\n- Expires: {{expires_at}}\n- Customer: {{customer_name}} ({{customer_email}})",
        ],
        'admin.quote_accepted' => [
            'name' => 'Admin: quote accepted',
            'subject' => 'Quote accepted: {{reference}}',
            'body' => "{{customer_name}} accepted the quote for booking **{{reference}}** ({{service}}), total **{{amount}}**.\n\nThe customer has been sent to checkout. You will receive a separate email once payment is confirmed.",
        ],
        'admin.quote_expired' => [
            'name' => 'Admin: quote expired',
            'subject' => 'Quote expired: {{reference}}',
            'body' => "The quote for booking **{{reference}}** ({{service}}) has expired without payment.\n\nCustomer: {{customer_name}} ({{customer_email}})\n\nThe booking has returned to Pending quote.",
        ],
        'admin.requote_requested' => [
            'name' => 'Admin: new quote requested',
            'subject' => 'New quote requested: {{reference}}',
            'body' => "{{customer_name}} asked for a new quote on booking **{{reference}}** ({{service}}) after the previous quote expired.\n\nReview the requirements and send an updated quote.",
        ],
        'admin.payment_received' => [
            'name' => 'Admin: payment received',
            'subject' => 'Payment received for {{reference}}',
            'body' => "A payment of **{{amount}}** was confirmed for booking **{{reference}}** ({{service}}).\n\nCustomer: {{customer_name}} ({{customer_email}})",
        ],
        'admin.payment_failed' => [
            'name' => 'Admin: payment failed',
            'subject' => 'Payment failed: {{reference}}',
            'body' => "A payment attempt for booking **{{reference}}** ({{service}}) failed.\n\nReason: {{failure_reason}}\nCustomer: {{customer_name}} ({{customer_email}})",
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
        'admin.booking_cancelled' => [
            'name' => 'Admin: booking cancelled',
            'subject' => 'Booking cancelled: {{reference}}',
            'body' => "Booking **{{reference}}** ({{service}}) has been cancelled.\n\nCustomer: {{customer_name}} ({{customer_email}})\n\n{{refund_note}}",
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
