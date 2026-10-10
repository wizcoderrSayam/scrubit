<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\CatalogCache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public homepage.
     */
    public function __invoke(): View
    {
        return view('home', [
            'services' => Service::visibleCatalog(),
            'testimonials' => CatalogCache::models('home.testimonials', Testimonial::class, fn () => Testimonial::query()->published()->get()),
        ]);
    }
}
