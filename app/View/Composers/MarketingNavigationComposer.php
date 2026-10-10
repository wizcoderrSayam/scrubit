<?php

namespace App\View\Composers;

use App\Models\Service;
use Illuminate\View\View;

class MarketingNavigationComposer
{
    /**
     * Share the public service list with the marketing header and footer.
     */
    public function compose(View $view): void
    {
        $view->with('services', Service::visibleCatalog());
    }
}
