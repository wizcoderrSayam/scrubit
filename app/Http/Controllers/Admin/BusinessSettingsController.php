<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBusinessSettingsRequest;
use App\Services\SettingsService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
}
