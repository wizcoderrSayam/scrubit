<?php

namespace Tests\Feature\Http\Controllers\Public;

use App\Enums\EnquiryType;
use App\Models\Enquiry;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Notifications\Admin\NewEnquiryNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_are_listed_by_category_with_customize_and_book_buttons(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Residential']);
        $service = Service::factory()->for($category, 'category')->create(['name' => 'Recurring Cleaning']);
        Service::factory()->for($category, 'category')->inactive()->create(['name' => 'Hidden Service']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Residential')
            ->assertSee('Recurring Cleaning')
            ->assertSee('Customize &amp; Book', false)
            ->assertSee(route('booking.start', $service))
            ->assertDontSee('Hidden Service');

        $this->get(route('services.category', $category))->assertOk()->assertSee('Recurring Cleaning');
    }

    public function test_service_detail_page_is_generated_dynamically_with_seo_metadata(): void
    {
        $service = Service::factory()->create([
            'name' => 'Move-In / Move Out Cleaning',
            'slug' => 'move-in-move-out-cleaning',
            'seo_title' => 'Move Cleaning Experts',
            'seo_description' => 'Spotless handovers.',
        ]);

        $this->get('/services/move-in-move-out-cleaning')
            ->assertOk()
            ->assertSee('<title>Move Cleaning Experts | Scrubit</title>', false)
            ->assertSee('<meta name="description" content="Spotless handovers.">', false)
            ->assertSee('<link rel="canonical" href="'.route('services.show', $service).'">', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_services_in_hidden_categories_are_not_reachable(): void
    {
        $service = Service::factory()->for(ServiceCategory::factory()->inactive(), 'category')->create();

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_services_index_nests_sub_categories_that_have_services(): void
    {
        $parent = ServiceCategory::factory()->create(['name' => 'Residential', 'description' => 'Houses and apartments']);
        $child = ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens']);
        Service::factory()->for($child, 'category')->create(['name' => 'Oven Cleaning']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSeeInOrder(['Residential', 'Kitchens', 'Oven Cleaning']);
    }

    public function test_category_page_shows_breadcrumbs_child_links_and_subtree_services(): void
    {
        $parent = ServiceCategory::factory()->create(['name' => 'Residential', 'slug' => 'residential']);
        $child = ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens', 'slug' => 'kitchens']);
        Service::factory()->for($child, 'category')->create(['name' => 'Oven Cleaning']);

        $this->get(route('services.category', $parent))
            ->assertOk()
            ->assertSee('Oven Cleaning')
            ->assertSee(route('services.category', $child), false);

        $this->get(route('services.category', $child))
            ->assertOk()
            ->assertSee(route('services.category', $parent), false)
            ->assertSeeInOrder(['Services', 'Residential', 'Kitchens'])
            ->assertSee('Oven Cleaning');
    }

    public function test_hidden_ancestor_hides_the_subtree_from_the_public_site(): void
    {
        $parent = ServiceCategory::factory()->inactive()->create(['name' => 'Residential', 'slug' => 'residential']);
        $child = ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens', 'slug' => 'kitchens']);
        $service = Service::factory()->for($child, 'category')->create(['name' => 'Oven Cleaning', 'slug' => 'oven-cleaning']);

        $this->get(route('services.index'))->assertOk()->assertDontSee('Oven Cleaning')->assertDontSee('Kitchens');
        $this->get(route('services.category', $child))->assertNotFound();
        $this->get(route('services.show', $service))->assertNotFound();
        $this->get(route('booking.start', $service))->assertNotFound();
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertDontSee(route('services.category', $child), false)
            ->assertDontSee(route('services.show', $service), false);
    }

    public function test_sitemap_lists_active_services_and_published_pages(): void
    {
        $service = Service::factory()->create();
        $inactive = Service::factory()->inactive()->create();
        Page::factory()->create(['slug' => 'terms']);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('services.show', $service), false)
            ->assertSee(route('pages.show', 'terms'), false)
            ->assertDontSee(route('services.show', $inactive), false);
    }

    public function test_robots_txt_references_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_legacy_wordpress_urls_redirect_permanently(): void
    {
        $this->get('/recurring-cleaning')->assertRedirect(route('services.show', 'recurring-cleaning'))->assertStatus(301);
        $this->get('/product/home-cleaning')->assertRedirect(route('services.show', 'home-cleaning'))->assertStatus(301);
        $this->get('/enquiry')->assertRedirect('/request-a-quote')->assertStatus(301);
    }

    public function test_quote_request_is_stored_and_admins_are_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->post(route('quote.store'), [
            'name' => 'Alex Quote', 'email' => 'alex@example.com', 'phone' => '604-555-0111',
            'service_id' => $service->id, 'message' => '3 bedroom house',
        ])->assertRedirect(route('quote.create'));

        $enquiry = Enquiry::query()->sole();
        $this->assertSame(EnquiryType::Quote, $enquiry->type);
        Notification::assertSentTo($admin, NewEnquiryNotification::class);
    }

    public function test_quote_request_notification_actually_sends_without_error(): void
    {
        User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->post(route('quote.store'), [
            'name' => 'Alex Quote', 'email' => 'alex@example.com', 'phone' => '604-555-0111',
            'service_id' => $service->id, 'message' => '3 bedroom house',
        ])->assertRedirect(route('quote.create'));

        $this->assertDatabaseHas('email_logs', ['template_key' => 'admin.new_enquiry']);
    }

    public function test_contact_form_rejects_bots_filling_the_honeypot(): void
    {
        $this->post(route('contact.store'), ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'spam', 'website' => 'http://spam.test'])
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount(Enquiry::class, 0);
    }

    public function test_enquiry_endpoints_are_rate_limited(): void
    {
        Notification::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact.store'), ['name' => 'A', 'email' => 'a@example.com', 'message' => 'Hi'])->assertRedirect();
        }

        $this->post(route('contact.store'), ['name' => 'A', 'email' => 'a@example.com', 'message' => 'Hi'])->assertTooManyRequests();
    }

    public function test_about_and_faq_pages_render(): void
    {
        Page::factory()->create(['slug' => 'about', 'title' => 'About Our Company']);

        $this->get(route('about'))->assertOk()->assertSee('About Our Company');
        $this->get(route('faq'))->assertOk();
        $this->get(route('contact'))->assertOk()->assertSee('Info@scrubit.ca');
    }
}
