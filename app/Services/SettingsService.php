<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Support\Money;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettingsService
{
    public const CACHE_KEY = 'business_settings';

    public const CACHE_TTL_SECONDS = 3600;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolved = null;

    public function __construct(private Cache $cache) {}

    /**
     * Get a single setting, falling back to its configured default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get every known setting with stored values applied over the defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->resolved ??= array_merge(
            $this->defaults(),
            $this->cache->remember(
                self::CACHE_KEY,
                self::CACHE_TTL_SECONDS,
                fn (): array => BusinessSetting::query()
                    ->whereIn('key', array_keys($this->defaults()))
                    ->pluck('value', 'key')
                    ->all(),
            ),
        );
    }

    /**
     * Persist the given settings and clear the cached values.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws InvalidArgumentException
     */
    public function set(array $values): void
    {
        $unknownKeys = array_diff(array_keys($values), array_keys($this->defaults()));

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException('Unknown business setting(s): '.implode(', ', $unknownKeys));
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                BusinessSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        $this->flush();
        DB::afterCommit(fn () => $this->flush());
    }

    /**
     * The customer-facing add-on pricing note, e.g. "Starting $25/hr add on services".
     */
    public function addonNote(): string
    {
        return str_replace(
            '{rate}',
            Money::formatCompact((int) $this->get('addon_note.rate_cents', 0), (string) $this->get('business.currency', 'CAD')),
            (string) $this->get('addon_note.text', ''),
        );
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return config('scrubit.settings', []);
    }
}
