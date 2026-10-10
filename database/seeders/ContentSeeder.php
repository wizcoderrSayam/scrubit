<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Service;
use App\Models\TaxRate;
use App\Models\Testimonial;
use App\Services\EmailTemplateRenderer;
use Illuminate\Database\Seeder;

/**
 * Pages, FAQs, testimonials (verbatim from scrubit.ca), email templates and default tax.
 * Existing records are never overwritten so admin edits survive re-seeding.
 */
class ContentSeeder extends Seeder
{
    private const DRAFT_NOTICE = "> **DRAFT: review with your legal advisor before publishing.** This template is provided as a starting point and is not legal advice.\n\n";

    public function run(): void
    {
        $this->pages();
        $this->faqs();
        $this->testimonials();
        $this->emailTemplates();

        if (! TaxRate::query()->exists()) {
            TaxRate::create(['name' => 'GST', 'rate_bps' => 500, 'is_active' => true, 'sort_order' => 1]);
        }
    }

    private function pages(): void
    {
        $pages = [
            'about' => [
                'title' => 'About Our Company',
                'seo_description' => 'Scrubit is a professional cleaning company delivering reliable, high-quality cleaning services for homes and businesses.',
                'body' => "Scrubit is a professional cleaning company committed to delivering reliable, high-quality cleaning services for homes and businesses. We believe a clean space creates comfort, productivity, and peace of mind.\n\nOur experienced and trained cleaning professionals focus on detail, consistency, and care in every job. From recurring and one-time cleaning to commercial, janitorial, and move-in/move-out services, we offer customized cleaning solutions tailored to your lifestyle, schedule, and specific requirements.\n\n## Our Mission\n\nTo provide reliable, high-quality cleaning services that create healthy, comfortable, and spotless spaces.\n\n## Our Vision\n\nTo become a trusted leader in the cleaning industry by setting high standards for service, sustainability, and customer satisfaction.\n\n## Our Values\n\nQuality, trust and professionalism: consistent service delivery with attention to detail and respect for every client's space.",
            ],
            'terms' => [
                'title' => 'Terms and Conditions',
                'seo_description' => 'Terms and conditions for booking cleaning services with Scrubit.',
                'body' => self::DRAFT_NOTICE."## 1. Bookings\n\nA booking request is not confirmed until payment for the approved price has been received. For services priced by quote, we review your requirements and send a quote; you are not charged until you approve it.\n\n## 2. Pricing and payment\n\nPrices are shown in the currency displayed at checkout and include applicable taxes where stated. Payments are processed by our payment provider; we do not store card details.\n\n## 3. Access and preparation\n\nYou agree to provide safe access to the property at the scheduled time and to tell us about any hazards, pets or special conditions.\n\n## 4. Changes and cancellations\n\nCancellations are handled under our Cancellation and Refund Policy.\n\n## 5. Liability\n\n[To be completed by the business with its legal advisor.]\n\n## 6. Contact\n\nQuestions about these terms can be sent to the contact details on our Contact page.",
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'seo_description' => 'How Scrubit collects, uses and protects your personal information.',
                'body' => self::DRAFT_NOTICE."## Information we collect\n\nWhen you book or contact us we collect your name, email address, phone number, service address and the details you provide about your cleaning requirements.\n\n## How we use it\n\nWe use this information to prepare quotes, schedule and deliver services, process payments, and contact you about your bookings.\n\n## Payments\n\nPayments are handled by our payment provider. We do not receive or store your full card number or security code.\n\n## Sharing\n\nWe do not sell your personal information. [List any service providers, e.g. email and payment processors.]\n\n## Retention and your rights\n\n[To be completed by the business with its legal advisor, including how to request access to or deletion of your information.]\n\n## Contact\n\nSend privacy questions to the email address on our Contact page.",
            ],
            'cancellation-policy' => [
                'title' => 'Cancellation and Refund Policy',
                'seo_description' => 'How to cancel or change a Scrubit booking and how refunds are handled.',
                'body' => self::DRAFT_NOTICE."## Before payment\n\nYou can cancel a booking that has not been paid at any time from your booking page. No charge applies.\n\n## After payment\n\nYou can request cancellation of a paid booking from your booking page until the cancellation cut-off shown there before your visit. Our team reviews each request and confirms the outcome by email.\n\n## Refunds\n\nApproved refunds are issued to the original payment method. [Specify any fees or partial refunds that apply.]\n\n## Late cancellations\n\nFor changes after the cut-off, please call us. [Specify the late-cancellation terms.]",
            ],
        ];

        foreach ($pages as $slug => $attributes) {
            Page::query()->firstOrCreate(['slug' => $slug], [...$attributes, 'is_published' => true]);
        }
    }

    private function faqs(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $faqs = [
            ['How do I book a cleaning?', 'Choose a service, then describe what you need in our quick booking chat: your priorities, the property, your preferred date and address. Review everything on one page and submit.'],
            ['Why do I receive a quote before paying?', 'Every space is different. Our team reviews your requirements and sends a quote for your approval. You are never charged until you approve the price.'],
            ['What does the add-on note on the booking screen mean?', 'It shows the starting hourly rate for optional add-on services. Add-ons are only charged if you select them or approve them as part of your quote.'],
            ['How do I pay?', 'Once your price is approved, you pay securely online through our payment provider. We never see or store your card details.'],
            ['Can I change or cancel my booking?', 'Yes. Unpaid bookings can be cancelled at any time from your booking page. For paid bookings you can request cancellation before the cut-off described in our Cancellation and Refund Policy.'],
            ['Do I need an account to book?', 'No. You can book as a guest and use the secure link in your email to view your booking. Creating an account lets you see all your bookings and saved addresses in one place.'],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            Faq::create(['question' => $question, 'answer' => $answer, 'sort_order' => $index + 1, 'is_published' => true]);
        }
    }

    private function testimonials(): void
    {
        if (Testimonial::query()->exists()) {
            return;
        }

        $moveService = Service::query()->where('slug', 'move-in-move-out-cleaning')->value('id');

        $testimonials = [
            ['Rachel', 'Scrubit has been an absolute pleasure to work with. The cleaners are professional, punctual, and very thorough. Our home feels fresh and spotless after every visit. Their attention to detail truly sets them apart.', null],
            ['Emily', 'From start to finish, Scrubit delivered excellent service. They respected our schedule, worked efficiently, and left our space spotless. Their professionalism and consistency keep us coming back.', null],
            ['Daniel', 'We use Scrubit for our office cleaning, and the results are consistently excellent. The team is reliable, respectful, and always arrives on time. Their eco-friendly products are a big plus for us. Outstanding service overall.', null],
            ['Sophia', "I booked a move-out cleaning with Scrubit, and they exceeded my expectations. The apartment looked brand new afterward. The booking process was easy, and pricing was very transparent. I couldn't be happier with the results.", $moveService],
        ];

        foreach ($testimonials as $index => [$author, $body, $serviceId]) {
            Testimonial::create(['author_name' => $author, 'body' => $body, 'service_id' => $serviceId, 'sort_order' => $index + 1, 'is_published' => true]);
        }
    }

    private function emailTemplates(): void
    {
        foreach (EmailTemplateRenderer::DEFAULTS as $key => $template) {
            if (EmailTemplate::query()->where('key', $key)->exists()) {
                continue;
            }

            $model = new EmailTemplate;
            $model->forceFill(['key' => $key, ...$template])->save();
        }
    }
}
