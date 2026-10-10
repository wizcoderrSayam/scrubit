<?php

namespace App\Models;

use App\Support\CatalogCache;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

#[Fillable(['parent_id', 'name', 'slug', 'description', 'seo_title', 'seo_description', 'sort_order', 'is_active'])]
class ServiceCategory extends Model
{
    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    /**
     * Services listed in this category, including those that use it as an additional category.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_service_category');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Every category, keyed by id, from the public catalog cache.
     *
     * @return Collection<int, self>
     */
    public static function cachedTree(): Collection
    {
        return CatalogCache::models(
            'services.tree.categories',
            self::class,
            fn () => self::query()->ordered()->get(),
        )->keyBy(fn (self $category): int => (int) $category->id);
    }

    /**
     * Ids of every category nested under this one.
     *
     * @param  Collection<int, self>|null  $tree  keyed by id; loaded once when omitted
     * @return list<int>
     */
    public function descendantIds(?Collection $tree = null): array
    {
        $grouped = self::indexed($tree)->groupBy(fn (self $category): int => (int) ($category->parent_id ?? 0));
        $ids = [];
        $stack = [(int) $this->id];
        $seen = [(int) $this->id => true];

        while ($stack !== []) {
            $id = array_pop($stack);

            foreach ($grouped->get($id, []) as $child) {
                $childId = (int) $child->id;

                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $ids[] = $childId;
                $stack[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * Ancestors from the root down to this category's parent.
     *
     * @param  Collection<int, self>|null  $tree
     * @return Collection<int, self>
     */
    public function ancestors(?Collection $tree = null): Collection
    {
        $map = self::indexed($tree);
        $chain = [];
        $current = $this->parent_id !== null ? (int) $this->parent_id : null;
        $seen = [];

        while ($current !== null && isset($map[$current]) && ! isset($seen[$current])) {
            $seen[$current] = true;
            $chain[] = $map[$current];
            $current = $map[$current]->parent_id !== null ? (int) $map[$current]->parent_id : null;
        }

        return new Collection(array_reverse($chain));
    }

    /**
     * True when this category and every ancestor is active.
     *
     * A parent missing from $tree counts as hidden, so an active-only map still hides the subtree.
     *
     * @param  Collection<int, self>|null  $tree
     */
    public function isVisibleInTree(?Collection $tree = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $map = self::indexed($tree);
        $current = $this->parent_id !== null ? (int) $this->parent_id : null;
        $seen = [];

        while ($current !== null) {
            if (isset($seen[$current]) || ! isset($map[$current]) || ! $map[$current]->is_active) {
                return false;
            }

            $seen[$current] = true;
            $current = $map[$current]->parent_id !== null ? (int) $map[$current]->parent_id : null;
        }

        return true;
    }

    /**
     * Depth-first rows for admin lists and selects.
     *
     * Passing $except drops that category and its descendants so a category cannot be parented to itself.
     *
     * @param  Collection<int, self>|null  $tree
     * @return list<array{category: self, depth: int, path: string}>
     */
    public static function flattenTree(?self $except = null, ?Collection $tree = null): array
    {
        $map = self::indexed($tree);

        if ($except !== null && $except->exists) {
            unset($map[(int) $except->id]);

            foreach ($except->descendantIds($map) as $id) {
                unset($map[$id]);
            }
        }

        $grouped = $map->groupBy(fn (self $category): int => (int) ($category->parent_id ?? 0));
        $rows = [];

        $walk = function (int $parentId, int $depth, string $prefix) use (&$walk, $grouped, &$rows): void {
            $siblings = ($grouped->get($parentId) ?? new Collection)->sortBy(
                fn (self $category): string => sprintf('%08d-%s-%08d', $category->sort_order, $category->name, $category->id),
            );

            foreach ($siblings as $category) {
                $path = $prefix === '' ? $category->name : $prefix.' / '.$category->name;
                $rows[] = [
                    'category' => $category,
                    'depth' => $depth,
                    'path' => $path,
                ];
                $walk((int) $category->id, $depth + 1, $path);
            }
        };

        $walk(0, 0, '');

        return $rows;
    }

    /**
     * Top-level categories whose visible subtree contains at least one active service.
     *
     * @param  Collection<int, self>  $categories
     * @param  SupportCollection<int, SupportCollection<int, Service>>  $servicesByCategory
     * @return list<array{category: self, services: SupportCollection<int, Service>, children: list<mixed>}>
     */
    public static function visibleForest(Collection $categories, SupportCollection $servicesByCategory): array
    {
        $map = self::indexed($categories);
        $grouped = $map->groupBy(fn (self $category): int => (int) ($category->parent_id ?? 0));

        $nodes = function (int $parentId) use (&$nodes, $grouped, $map, $servicesByCategory): array {
            $result = [];
            $siblings = ($grouped->get($parentId) ?? new Collection)->sortBy(
                fn (self $category): string => sprintf('%08d-%s-%08d', $category->sort_order, $category->name, $category->id),
            );

            foreach ($siblings as $category) {
                if (! $category->isVisibleInTree($map)) {
                    continue;
                }

                $children = $nodes((int) $category->id);
                $services = $servicesByCategory->get((int) $category->id, collect())->values();

                if ($services->isEmpty() && $children === []) {
                    continue;
                }

                $result[] = [
                    'category' => $category,
                    'services' => $services,
                    'children' => $children,
                ];
            }

            return $result;
        };

        return $nodes(0);
    }

    /**
     * @param  Collection<int, self>|SupportCollection<int, self>|null  $tree
     * @return Collection<int, self>
     */
    private static function indexed(Collection|SupportCollection|null $tree): Collection
    {
        $categories = $tree ?? self::query()->get();

        return new Collection($categories->keyBy(fn (self $category): int => (int) $category->id)->all());
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }
}
