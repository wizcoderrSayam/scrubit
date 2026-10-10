<?php

namespace App\View\Composers;

use App\Services\SettingsService;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class BusinessComposer
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Share business contact details with the site layouts.
     * Mail credentials are deliberately excluded; nothing in these layouts needs them.
     */
    public function compose(View $view): void
    {
        $view->with('business', Arr::except($this->settings->all(), [
            'mail.host', 'mail.port', 'mail.username', 'mail.password', 'mail.from_address',
        ]));
    }
}
