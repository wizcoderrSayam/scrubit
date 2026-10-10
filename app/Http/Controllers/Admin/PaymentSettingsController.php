<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    /**
     * Payment provider configuration lives in the environment; this page shows it read-only.
     */
    public function __invoke(SettingsService $settings): View
    {
        $driver = (string) config('payments.driver');

        return view('admin.settings.payments', [
            'driver' => $driver,
            'isDemo' => $driver === 'demo',
            'currency' => $settings->get('business.currency'),
            'webhookUrl' => route('webhooks.payments', $driver),
            'checkoutTtl' => (int) config('payments.checkout_ttl_minutes'),
        ]);
    }
}
