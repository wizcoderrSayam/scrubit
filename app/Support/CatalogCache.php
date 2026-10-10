<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for public catalog data. Bumping the version invalidates every catalog entry at once.
 *
 * The cache store is configured not to unserialize objects (cache.serializable_classes = false),
 * so only scalars and arrays are stored. Models are cached as raw attribute arrays and re-hydrated.
 */
final class CatalogCache
{
    private const VERSION_KEY = 'catalog.version';

    private const TTL_SECONDS = 3600;

    /**
     * Cache a scalar or array value.
     *
     * @template TValue of scalar|array<array-key, mixed>|null
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(self::key($key), self::TTL_SECONDS, $callback);
    }

    /**
     * Cache a flat model collection (no eager-loaded relations) and return fresh model instances.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  Closure(): Collection<int, TModel>  $query
     * @return Collection<int, TModel>
     */
    public static function models(string $key, string $modelClass, Closure $query): Collection
    {
        $rows = self::remember($key, fn (): array => $query()->map(fn (Model $model): array => $model->getAttributes())->values()->all());

        return $modelClass::hydrate($rows);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    private static function key(string $key): string
    {
        return 'catalog.v'.Cache::get(self::VERSION_KEY, 1).'.'.$key;
    }
}
