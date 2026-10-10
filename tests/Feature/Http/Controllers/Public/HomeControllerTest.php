<?php

namespace Tests\Feature\Http\Controllers\Public;

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
}
