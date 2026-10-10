<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\EmailTemplate;
use App\Models\Faq;
use App\Models\Page;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_tax_rates_are_stored_in_basis_points(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.tax.store'), ['name' => 'PST', 'rate' => '7.25', 'is_active' => '1'])->assertRedirect();

        $this->assertSame(725, TaxRate::query()->where('name', 'PST')->value('rate_bps'));
    }

    public function test_weekly_availability_and_closed_dates_are_saved(): void
    {
        $days = collect(range(0, 6))->mapWithKeys(fn ($day) => [$day => ['is_open' => $day === 0 ? '0' : '1', 'windows' => ['morning']]])->all();

        $this->actingAs($this->admin)->put(route('admin.settings.availability.update'), ['days' => $days])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.settings.availability.exceptions.store'), ['date' => now()->addDays(3)->toDateString(), 'reason' => 'Holiday'])->assertRedirect();

        $this->assertFalse(AvailabilityRule::query()->where('weekday', 0)->value('is_open'));
        $this->assertSame(['morning'], AvailabilityRule::query()->where('weekday', 1)->first()->windows);
        $this->assertDatabaseCount(AvailabilityException::class, 1);
    }

    public function test_page_content_is_editable_and_rendered_without_raw_html(): void
    {
        $page = Page::factory()->create(['slug' => 'terms']);

        $this->actingAs($this->admin)->put(route('admin.pages.update', $page), [
            'title' => 'Terms and Conditions',
            'body' => "## Bookings\n\n<img src=x onerror=alert(1)> Read carefully.",
            'is_published' => '1',
        ])->assertRedirect();

        $this->get(route('pages.show', 'terms'))
            ->assertOk()
            ->assertSee('<h2>Bookings</h2>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_faqs_can_be_created_and_hidden(): void
    {
        $this->actingAs($this->admin)->post(route('admin.faqs.store'), ['question' => 'Do you bring supplies?', 'answer' => 'Yes.', 'is_published' => '1']);
        $faq = Faq::query()->sole();

        $this->get(route('faq'))->assertSee('Do you bring supplies?');

        $this->actingAs($this->admin)->put(route('admin.faqs.update', $faq), ['question' => 'Do you bring supplies?', 'answer' => 'Yes.', 'is_published' => '0']);
        $this->get(route('faq'))->assertDontSee('Do you bring supplies?');
    }

    public function test_homepage_content_is_editable(): void
    {
        $this->actingAs($this->admin)->put(route('admin.content.home.update'), $this->homepageContent())->assertRedirect();

        app(SettingsService::class)->flush();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Spotless every time')
            ->assertSee('We clean homes and offices.')
            ->assertSee('Fully insured team')
            ->assertDontSee('Numbers That Speak');
    }

    public function test_homepage_shows_every_stat_after_one_count_is_saved_above_zero(): void
    {
        $this->actingAs($this->admin)->put(route('admin.content.home.update'), $this->homepageContent([
            'houses_cleaned' => 12,
        ]))->assertRedirect();

        app(SettingsService::class)->flush();

        $this->assertSame(12, app(SettingsService::class)->get('home.stats.houses_cleaned'));
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Numbers That Speak')
            ->assertSee('Houses Cleaned')
            ->assertSee('Happy Customers')
            ->assertSee('Expert Cleaners')
            ->assertSee('Years of Experience')
            ->assertSee('12+');
    }

    public function test_homepage_stats_reject_a_negative_count(): void
    {
        $this->actingAs($this->admin)->put(route('admin.content.home.update'), $this->homepageContent([
            'houses_cleaned' => -1,
        ]))->assertSessionHasErrors([
            'houses_cleaned' => 'The houses cleaned field must be at least 0.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function homepageContent(array $overrides = []): array
    {
        return array_merge([
            'hero_eyebrow' => 'Spotless every time',
            'intro' => 'We clean homes and offices.',
            'cta_heading' => 'Book today',
            'cta_text' => 'Fast, friendly service.',
            'why_items' => "Insured | Fully insured team\nFlexible | Plans that fit",
            'houses_cleaned' => 0,
            'happy_customers' => 0,
            'expert_cleaners' => 0,
            'years_experience' => 0,
        ], $overrides);
    }

    public function test_email_templates_can_be_edited_and_restored(): void
    {
        $template = new EmailTemplate;
        $template->forceFill(['key' => 'customer.quote_sent', 'name' => 'Quote', 'subject' => 'Old', 'body' => 'Old body'])->save();

        $this->actingAs($this->admin)->put(route('admin.email-templates.update', $template), ['subject' => 'Your quote {{reference}}', 'body' => 'Hi {{customer_name}}'])->assertRedirect();
        $this->assertSame('Your quote {{reference}}', $template->fresh()->subject);

        $this->actingAs($this->admin)->post(route('admin.email-templates.reset', $template))->assertRedirect();
        $this->assertSame('Your quote for {{service}} ({{reference}})', $template->fresh()->subject);
    }

    public function test_booking_settings_change_the_minimum_notice(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.business.edit'))->assertOk()->assertSee('Minimum notice');

        app(SettingsService::class)->set(['booking.min_lead_days' => 3]);

        $this->assertSame(now()->addDays(3)->toDateString(), app(AvailabilityService::class)->earliestDate()->toDateString());
    }
}
