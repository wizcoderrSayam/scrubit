<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * SMTP credentials saved from Admin > Settings > Business override the .env mail config, when present.
 * Falls back silently to .env (e.g. on a fresh install, before migrations have run).
 */
class MailSettingsApplier
{
    public function __construct(private SettingsService $settings) {}

    public function apply(): void
    {
        try {
            if (! Schema::hasTable('business_settings')) {
                return;
            }

            $username = trim((string) $this->settings->get('mail.username'));
            $encryptedPassword = (string) $this->settings->get('mail.password');

            if ($username === '' || $encryptedPassword === '') {
                return;
            }

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', (string) $this->settings->get('mail.host', 'smtp.gmail.com'));
            Config::set('mail.mailers.smtp.port', (int) $this->settings->get('mail.port', 587));
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', Crypt::decryptString($encryptedPassword));

            $fromAddress = trim((string) $this->settings->get('mail.from_address'));
            Config::set('mail.from.address', $fromAddress !== '' ? $fromAddress : $username);
        } catch (Throwable) {
            // Keep whatever .env provides; the database settings are an optional override.
        }
    }
}
