<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
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
            'services' => CatalogCache::models('home.services.tree', Service::class, function () {
                $tree = ServiceCategory::query()->get()->keyBy(fn (ServiceCategory $category): int => (int) $category->id);
                $visibleIds = $tree
                    ->filter(fn (ServiceCategory $category): bool => $category->isVisibleInTree($tree))
                    ->map(fn (ServiceCategory $category): int => (int) $category->id)
                    ->all();

                return Service::query()->active()->whereIn('service_category_id', $visibleIds)->ordered()->get();
            }),
            'testimonials' => CatalogCache::models('home.testimonials', Testimonial::class, fn () => Testimonial::query()->published()->get()),
        ]);
    }
}
