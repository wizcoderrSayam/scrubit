<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\CatalogCache;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * All active services grouped by category.
     */
    public function index(): View
    {
        $categories = CatalogCache::models('services.index.categories', ServiceCategory::class, fn () => ServiceCategory::query()->active()->ordered()->get());
        $services = CatalogCache::models('services.index.services', Service::class, fn () => Service::query()->active()->ordered()->get())
            ->groupBy('service_category_id');

        return view('services.index', [
            'categories' => $categories
                ->each(fn (ServiceCategory $category) => $category->setRelation('services', $services->get($category->id, collect())->values()))
                ->filter(fn (ServiceCategory $category) => $category->services->isNotEmpty())
                ->values(),
        ]);
    }

    public function category(ServiceCategory $category): View
    {
        abort_unless($category->is_active, 404);

        return view('services.category', [
            'category' => $category,
            'services' => CatalogCache::models("category.{$category->id}.services", Service::class, fn () => $category->services()->active()->ordered()->get()),
        ]);
    }

    public function show(Service $service): View
    {
        $service->loadMissing('category');
        abort_unless($service->is_active && $service->category?->is_active, 404);

        $service->load(['images', 'addons' => fn ($query) => $query->active()->orderBy('sort_order')]);

        return view('services.show', [
            'service' => $service,
            'related' => Service::query()
                ->active()
                ->where('service_category_id', $service->service_category_id)
                ->whereKeyNot($service->id)
                ->ordered()
                ->limit(3)
                ->get(),
        ]);
    }
}
