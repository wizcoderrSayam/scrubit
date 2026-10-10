<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Active services grouped under visible top-level categories.
     */
    public function index(): View
    {
        $categories = ServiceCategory::cachedTree();
        $services = CatalogCache::models('services.index.services', Service::class, fn () => Service::query()->active()->ordered()->get());

        return view('services.index', [
            'categories' => ServiceCategory::visibleForest($categories, $this->servicesByCategory($services)),
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
            fn () => Service::query()->active()->inCategories($visibleIds)->ordered()->get(),
        )->load('categories')->sortBy(fn (Service $service): string => sprintf(
            '%08d-%08d-%s',
            $this->bestCategoryRank($service, $visibleIds, $rank),
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
        $tree = ServiceCategory::cachedTree();
        $service->loadMissing(['category', 'categories']);
        abort_unless($service->isVisibleInCatalog($tree), 404);

        $service->load(['images', 'addons' => fn ($query) => $query->active()->orderBy('sort_order')]);

        $visibleIds = $tree
            ->filter(fn (ServiceCategory $category): bool => $category->isVisibleInTree($tree))
            ->map(fn (ServiceCategory $category): int => (int) $category->id)
            ->all();
        $sharedIds = array_values(array_intersect(
            $service->categories->map(fn (ServiceCategory $category): int => (int) $category->id)->all(),
            $visibleIds,
        ));

        return view('services.show', [
            'service' => $service,
            'related' => Service::query()
                ->active()
                ->inCategories($sharedIds)
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

    /**
     * Place each service in every category it is assigned to.
     *
     * @param  Collection<int, Service>  $services
     * @return SupportCollection<int, SupportCollection<int, Service>>
     */
    private function servicesByCategory(Collection $services): SupportCollection
    {
        $grouped = collect();

        foreach ($services->load('categories') as $service) {
            foreach ($service->categories as $category) {
                $categoryId = (int) $category->id;
                $grouped[$categoryId] ??= collect();
                $grouped[$categoryId]->push($service);
            }
        }

        return $grouped;
    }

    /**
     * Earliest tree position among this service's categories in the current subtree.
     *
     * @param  list<int>  $visibleIds
     * @param  array<int, int>  $rank
     */
    private function bestCategoryRank(Service $service, array $visibleIds, array $rank): int
    {
        $positions = $service->categories
            ->map(function (ServiceCategory $category) use ($visibleIds, $rank): ?int {
                $id = (int) $category->id;

                if (! in_array($id, $visibleIds, true)) {
                    return null;
                }

                return $rank[$id] ?? null;
            })
            ->filter(fn (?int $position): bool => $position !== null);

        return $positions->min() ?? 999999;
    }
}
