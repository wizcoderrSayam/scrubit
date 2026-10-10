<?php

namespace App\View\Composers;

use App\Services\SettingsService;
use Illuminate\View\View;

class BusinessComposer
{
    public function __construct(private SettingsService $settings) {}

    /**
     * Share business contact details with the site layouts.
     */
    public function compose(View $view): void
    {
        $view->with('business', $this->settings->all());
    }
}
