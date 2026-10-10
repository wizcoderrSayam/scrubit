<?php

namespace Tests\Feature\Http\Controllers\Public;

use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingDraftService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_homepage_with_business_contact_details(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Reliable, insured and detail focused')
            ->assertSee('+1 (778) 962-6999')
            ->assertSee('Info@scrubit.ca')
            ->assertSee('tel:+17789626999', false)
            ->assertDontSee('9876543210');
    }

    public function test_renders_updated_settings_from_the_admin_dashboard(): void
    {
        app(SettingsService::class)->set(['business.phone' => '+1 (604) 555-0100']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('+1 (604) 555-0100')
            ->assertDontSee('+1 (778) 962-6999');
    }

    public function test_escapes_html_in_business_settings(): void
    {
        app(SettingsService::class)->set(['business.name' => "Scrub<script>alert('x')</script>"]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Scrub&lt;script&gt;', false)
            ->assertDontSee("<script>alert('x')</script>", false);
    }

    public function test_hides_social_links_until_configured(): void
    {
        $this->get(route('home'))->assertDontSee('Follow us');

        app(SettingsService::class)->set(['social.instagram' => 'https://instagram.com/scrubit']);

        $this->get(route('home'))
            ->assertSee('Follow us')
            ->assertSee('https://instagram.com/scrubit', false);
    }

    public function test_homepage_puts_the_intro_in_the_about_teaser_and_posts_the_quote_form(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                'Get in touch',
                'About Our Company',
                'peace of mind',
            ])
            ->assertSee('href="#quote"', false)
            ->assertSee('action="'.route('quote.store').'"', false)
            ->assertSee('Discover more')
            ->assertDontSee('Numbers That Speak');
    }

    public function test_marketing_header_lists_services_and_keeps_faq_out_of_the_header(): void
    {
        $service = Service::factory()->create(['name' => 'Recurring Cleaning']);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Read more')
            ->assertDontSee('Customize &amp; Book', false);

        $header = strstr($response->getContent(), '<main', true);

        $this->assertIsString($header);
        $this->assertStringContainsString('Recurring Cleaning', $header);
        $this->assertStringContainsString('View all services', $header);
        $this->assertStringContainsString(route('about'), $header);
        $this->assertStringContainsString(route('contact'), $header);
        $this->assertStringNotContainsString(route('faq'), $header);
        $this->assertStringContainsString(route('faq'), $response->getContent());
        $this->assertStringContainsString(route('services.show', $service), $header);
    }

    public function test_booking_pages_keep_the_original_header_links(): void
    {
        $service = Service::factory()->create();
        $start = $this->get(route('booking.start', $service));
        $booking = Booking::query()->latest('id')->firstOrFail();
        $token = $start->getCookie(BookingDraftService::COOKIE)?->getValue();

        $response = $this->withCookie(BookingDraftService::COOKIE, (string) $token)
            ->get(route('booking.customize', $booking))
            ->assertOk();

        $header = strstr($response->getContent(), '<main', true);

        $this->assertIsString($header);
        $this->assertStringContainsString(route('faq'), $header);
        $this->assertStringNotContainsString('View all services', $header);
    }
}
