<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\BusinessSetting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class BusinessSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'business_name' => 'Scrubit',
            'business_tagline' => 'Professional Cleaning Services for Homes & Businesses',
            'business_phone' => '+1 (778) 962-6999',
            'business_email' => 'Info@scrubit.ca',
            'business_location' => 'Canada',
            'business_currency' => 'CAD',
            'social_facebook' => '',
            'social_instagram' => 'https://instagram.com/scrubit',
            'social_youtube' => '',
            'addon_note_text' => 'Starting {rate}/hr add on services',
            'addon_note_rate' => '27.50',
            'cancellation_cutoff_hours' => '24',
            'booking_min_lead_days' => '2',
            'booking_max_advance_days' => '60',
            'quote_default_expiry_days' => '10',
        ];
    }

    public function test_admin_sees_current_settings_and_addon_preview(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.settings.business.edit'))
            ->assertOk()
            ->assertSee('value="25.00"', false)
            ->assertSee('Starting $25/hr add on services');
    }

    public function test_forbids_customers_from_updating_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('admin.settings.business.update'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount(BusinessSetting::class, 0);
    }

    public function test_admin_saves_settings_with_rate_stored_in_cents(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.business.update'), $this->validPayload())
            ->assertRedirect(route('admin.settings.business.edit'))
            ->assertSessionHas('status', 'settings-saved');

        $settings = app(SettingsService::class);
        $settings->flush();
        $this->assertSame(2750, $settings->get('addon_note.rate_cents'));
        $this->assertSame(24, $settings->get('cancellation.cutoff_hours'));
        $this->assertSame('https://instagram.com/scrubit', $settings->get('social.instagram'));
        $this->assertNull($settings->get('social.facebook'));
        $this->assertSame('Starting $27.50/hr add on services', $settings->addonNote());
    }

    public function test_ignores_fields_that_are_not_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.business.update'), [...$this->validPayload(), 'app.debug' => '1', 'role' => 'admin'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(BusinessSetting::class, ['key' => 'app.debug']);
        $this->assertDatabaseMissing(BusinessSetting::class, ['key' => 'role']);
    }

    public function test_rejects_rate_with_more_than_two_decimal_places(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.business.update'), [...$this->validPayload(), 'addon_note_rate' => '25.555'])
            ->assertSessionHasErrors(['addon_note_rate' => 'Enter the rate in dollars, e.g. 25 or 25.50.']);

        $this->assertDatabaseCount(BusinessSetting::class, 0);
    }

    public function test_rejects_unsupported_currency_and_non_https_social_links(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.business.update'), [
                ...$this->validPayload(),
                'business_currency' => 'BTC',
                'social_facebook' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors(['business_currency', 'social_facebook']);
    }

    public function test_requires_core_business_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.business.update'), [])
            ->assertSessionHasErrors([
                'business_name',
                'business_phone',
                'business_email',
                'business_currency',
                'addon_note_text',
                'addon_note_rate',
                'cancellation_cutoff_hours',
            ]);
    }

    public function test_forbids_customers_from_sending_a_test_email(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.settings.test-email'))
            ->assertForbidden();
    }

    public function test_admin_can_send_a_test_email_to_the_business_address(): void
    {
        Mail::fake();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.settings.test-email'))
            ->assertRedirect(route('admin.settings.business.edit'))
            ->assertSessionHas('status', 'Test email sent to Info@scrubit.ca.');
    }

    public function test_admin_sees_a_friendly_error_when_the_test_email_fails_to_send(): void
    {
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('Connection could not be established with host smtp.gmail.com'));

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.settings.test-email'))
            ->assertRedirect(route('admin.settings.business.edit'));

        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('smtp.gmail.com', session('error'));
    }
}
