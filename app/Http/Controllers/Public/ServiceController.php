<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Active services grouped under visible top-level categories.
     */
    public function index(): View
    {
        $categories = ServiceCategory::cachedTree();
        $services = CatalogCache::models('services.index.services', Service::class, fn () => Service::query()->active()->ordered()->get())
            ->groupBy(fn (Service $service): int => (int) $service->service_category_id);

        return view('services.index', [
            'categories' => ServiceCategory::visibleForest($categories, $services),
        ]);
    }

    public function category(ServiceCategory $category): View
    {
        $tree = ServiceCategory::cachedTree();
        abort_unless($category->isVisibleInTree($tree), 404);

        $visibleIds = $this->visibleIds($category, $tree);
        $rank = [];

        foreach (ServiceCategory::flattenTree(tree: $tree) as $index => $row) {
            $rank[(int) $row['category']->id] = $index;
        }

        $services = CatalogCache::models(
            "category.{$category->id}.subtree.services",
            Service::class,
            fn () => Service::query()->active()->whereIn('service_category_id', $visibleIds)->ordered()->get(),
        )->sortBy(fn (Service $service): string => sprintf(
            '%08d-%08d-%s',
            $rank[(int) $service->service_category_id] ?? 999999,
            $service->sort_order,
            $service->name,
        ))->values();

        $children = $tree
            ->filter(fn (ServiceCategory $item): bool => (int) $item->parent_id === (int) $category->id && $item->isVisibleInTree($tree))
            ->sortBy(fn (ServiceCategory $item): string => sprintf('%08d-%s', $item->sort_order, $item->name))
            ->values();

        return view('services.category', [
            'category' => $category,
            'ancestors' => $category->ancestors($tree),
            'children' => $children,
            'services' => $services,
        ]);
    }

    public function show(Service $service): View
    {
        $service->loadMissing('category');
        abort_unless($service->is_active && $service->category?->isVisibleInTree(ServiceCategory::cachedTree()), 404);

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

    /**
     * This category and every visible descendant.
     *
     * @param  Collection<int, ServiceCategory>  $tree
     * @return list<int>
     */
    private function visibleIds(ServiceCategory $category, Collection $tree): array
    {
        $ids = array_merge([(int) $category->id], $category->descendantIds($tree));

        return array_values(array_filter(
            $ids,
            fn (int $id): bool => isset($tree[$id]) && $tree[$id]->isVisibleInTree($tree),
        ));
    }
}
