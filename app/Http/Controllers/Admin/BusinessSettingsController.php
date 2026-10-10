<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessSettingsRequest;
use App\Services\SettingsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class BusinessSettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

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
        ]);
    }

    /**
     * Save the business settings.
     */
    public function update(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        $this->settings->set($request->settings());

        return to_route('admin.settings.business.edit')->with('status', 'settings-saved');
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
