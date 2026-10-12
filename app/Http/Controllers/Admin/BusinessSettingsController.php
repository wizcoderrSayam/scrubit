<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessSettingsRequest;
use App\Services\ImageUploader;
use App\Services\SettingsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class BusinessSettingsController extends Controller
{
    public function __construct(private SettingsService $settings, private ImageUploader $images) {}

    /**
     * Show the business settings form.
     */
    public function edit(): View
    {
        return view('admin.settings.business', [
            'settings' => $this->settings->all(),
            'currencies' => UpdateBusinessSettingsRequest::CURRENCIES,
            'addonRate' => Money::toDecimal((int) $this->settings->get('addon_note.rate_cents', 0)),
            'addonNotePreview' => $this->settings->addonNote(),
            'hasMailPassword' => filled($this->settings->get('mail.password')),
        ]);
    }

    /**
     * Save the business settings.
     */
    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $values = $request->settings();

        // A blank password field means "keep what's already saved", not "clear it".
        $password = $request->validated('mail_password');
        if (filled($password)) {
            $values['mail.password'] = Crypt::encryptString($password);
        }

        $values += $this->brandImageSettings($request);

        $this->settings->set($values);

        return to_route('admin.settings.business.edit')->with('status', 'settings-saved');
    }

    /**
     * Store, replace or remove the logo and favicon, returning only the settings that changed.
     *
     * @return array<string, string|null>
     */
    private function brandImageSettings(UpdateBusinessSettingsRequest $request): array
    {
        $changes = [];

        foreach (['logo' => 'brand.logo', 'favicon' => 'brand.favicon'] as $field => $key) {
            $current = $this->settings->get($key);

            if ($request->hasFile($field)) {
                $changes[$key] = $this->images->store($request->file($field), 'brand');
            } elseif ($request->boolean("remove_{$field}")) {
                $changes[$key] = null;
            } else {
                continue;
            }

            $this->images->delete(is_string($current) ? $current : null);
        }

        return $changes;
    }

    /**
     * Send a one-off test email to the business address, to confirm the mail settings actually deliver.
     */
    public function sendTestEmail(): RedirectResponse
    {
        $email = trim((string) $this->settings->get('business.email'));

        if ($email === '') {
            return to_route('admin.settings.business.edit')->with('error', 'Set a business email above first.');
        }

        try {
            Mail::raw(
                'This is a test email from Scrubit. If you received it, your mail settings are working.',
                fn ($message) => $message->to($email)->subject('Scrubit test email'),
            );
        } catch (Throwable $exception) {
            // Log only the exception class, never its message, since mail-transport errors can echo back credentials.
            Log::warning('Test email failed to send.', ['exception' => $exception::class]);

            return to_route('admin.settings.business.edit')->with('error', 'Could not send the test email. Check your mail settings in .env and try again.');
        }

        return to_route('admin.settings.business.edit')->with('status', "Test email sent to {$email}.");
    }
}
