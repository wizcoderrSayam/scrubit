<?php

namespace Tests\Feature\Services;

use App\Models\BusinessSetting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_configured_default_when_nothing_is_stored(): void
    {
        $this->assertSame('+1 (778) 962-6999', app(SettingsService::class)->get('business.phone'));
    }

    public function test_stored_value_overrides_default_and_replaces_cached_copy(): void
    {
        $settings = app(SettingsService::class);
        $this->assertSame('Info@scrubit.ca', $settings->get('business.email'));

        $settings->set(['business.email' => 'bookings@example.com']);

        $this->assertSame('bookings@example.com', $settings->get('business.email'));
        $this->assertDatabaseHas(BusinessSetting::class, ['key' => 'business.email']);
        $freshInstance = new SettingsService($this->app->make('cache.store'));
        $this->assertSame('bookings@example.com', $freshInstance->get('business.email'));
    }

    public function test_rejects_unknown_setting_keys_without_saving_anything(): void
    {
        try {
            app(SettingsService::class)->set(['business.name' => 'Changed', 'app.debug' => true]);
            $this->fail('Expected InvalidArgumentException for an unknown key.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('app.debug', $exception->getMessage());
        }

        $this->assertDatabaseCount(BusinessSetting::class, 0);
    }

    public function test_addon_note_renders_exact_default_wording(): void
    {
        $this->assertSame('Starting $25/hr add on services', app(SettingsService::class)->addonNote());
    }

    public function test_addon_note_reflects_updated_rate(): void
    {
        $settings = app(SettingsService::class);

        $settings->set(['addon_note.rate_cents' => 3050]);

        $this->assertSame('Starting $30.50/hr add on services', $settings->addonNote());
    }
}
