<?php

namespace Tests\Feature;

use App\Services\MailSettingsApplier;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class MailSettingsOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_smtp_credentials_override_the_env_mail_config(): void
    {
        app(SettingsService::class)->set([
            'mail.host' => 'smtp.gmail.com',
            'mail.port' => 587,
            'mail.username' => 'business@scrubit.ca',
            'mail.password' => Crypt::encryptString('app-password-secret'),
            'mail.from_address' => 'business@scrubit.ca',
        ]);

        app(MailSettingsApplier::class)->apply();

        $this->assertSame('smtp', Config::get('mail.default'));
        $this->assertSame('smtp.gmail.com', Config::get('mail.mailers.smtp.host'));
        $this->assertSame(587, Config::get('mail.mailers.smtp.port'));
        $this->assertSame('business@scrubit.ca', Config::get('mail.mailers.smtp.username'));
        $this->assertSame('app-password-secret', Config::get('mail.mailers.smtp.password'));
        $this->assertSame('business@scrubit.ca', Config::get('mail.from.address'));
    }

    public function test_without_saved_credentials_the_env_mail_config_is_left_alone(): void
    {
        app(MailSettingsApplier::class)->apply();

        $this->assertSame('array', Config::get('mail.default'));
    }

    public function test_business_composer_excludes_mail_credentials_from_shared_view_data(): void
    {
        app(SettingsService::class)->set([
            'mail.username' => 'business@scrubit.ca',
            'mail.password' => Crypt::encryptString('app-password-secret'),
        ]);

        $shared = null;
        View::composer('home', function ($view) use (&$shared): void {
            $shared = $view->getData();
        });

        $this->get('/')->assertOk();

        $this->assertArrayHasKey('business', $shared);
        $this->assertArrayNotHasKey('mail.username', $shared['business']);
        $this->assertArrayNotHasKey('mail.password', $shared['business']);
    }
}
