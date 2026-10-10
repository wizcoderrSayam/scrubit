<?php

namespace App\Models;

use App\Enums\ServicePricingMethod;
use App\Support\CatalogCache;
use App\Support\Money;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'service_category_id', 'name', 'slug', 'short_description', 'description', 'inclusions', 'exclusions',
    'instructions', 'pricing_method', 'price_cents', 'hourly_rate_cents', 'estimated_minutes',
    'allows_recurring', 'chat_script', 'seo_title', 'seo_description', 'is_active', 'sort_order',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inclusions' => 'array',
            'exclusions' => 'array',
            'chat_script' => 'array',
            'pricing_method' => ServicePricingMethod::class,
            'price_cents' => 'integer',
            'hourly_rate_cents' => 'integer',
            'estimated_minutes' => 'integer',
            'allows_recurring' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * Every category this service is listed under, including the primary category.
     *
     * @return BelongsToMany<ServiceCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'service_service_category')->orderBy('name');
    }

    /**
     * @return HasMany<ServiceImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<ServiceAddon, $this>
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(ServiceAddon::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
     * Services assigned to any of the given categories.
     *
     * @param  Builder<self>  $query
     * @param  list<int>  $categoryIds
     */
    public function scopeInCategories(Builder $query, array $categoryIds): void
    {
        $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($categoryIds));
    }

    /**
     * Active services listed under at least one publicly visible category.
     *
     * @return Collection<int, self>
     */
    public static function visibleCatalog(): Collection
    {
        return CatalogCache::models('home.services.tree', self::class, function () {
            $tree = ServiceCategory::query()->get()->keyBy(fn (ServiceCategory $category): int => (int) $category->id);
            $visibleIds = $tree
                ->filter(fn (ServiceCategory $category): bool => $category->isVisibleInTree($tree))
                ->map(fn (ServiceCategory $category): int => (int) $category->id)
                ->all();

            return self::query()->active()->inCategories($visibleIds)->ordered()->get();
        });
    }

    /**
     * Active, and listed under at least one category that is visible in the tree.
     *
     * @param  Collection<int, ServiceCategory>|null  $tree
     */
    public function isVisibleInCatalog(?Collection $tree = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $tree ??= ServiceCategory::cachedTree();
        $this->loadMissing('categories');

        return $this->categories->contains(
            fn (ServiceCategory $category): bool => $category->isVisibleInTree($tree),
        );
    }

    /**
     * Replace the assigned categories and invalidate the public catalog.
     *
     * @param  list<int>  $categoryIds
     * @return array{attached: list<int>, detached: list<int>, updated: list<int>}
     */
    public function syncCategories(array $categoryIds): array
    {
        $changes = $this->categories()->sync($categoryIds);
        CatalogCache::flush();

        return $changes;
    }

    public function requiresQuote(): bool
    {
        return $this->pricing_method->requiresQuote();
    }

    public function featuredImageUrl(): ?string
    {
        return $this->featured_image ? Storage::disk('public')->url($this->featured_image) : null;
    }

    /**
     * Customer-facing pricing summary, e.g. "From $120" or "Custom quote".
     */
    public function priceLabel(string $currency = 'CAD'): string
    {
        return match ($this->pricing_method) {
            ServicePricingMethod::Fixed => $this->price_cents !== null ? Money::formatCompact($this->price_cents, $currency) : 'Custom quote',
            ServicePricingMethod::Hourly => $this->hourly_rate_cents !== null ? Money::formatCompact($this->hourly_rate_cents, $currency).'/hr' : 'Custom quote',
            ServicePricingMethod::StartingQuote => $this->price_cents !== null ? 'From '.Money::formatCompact($this->price_cents, $currency) : 'Custom quote',
            ServicePricingMethod::CustomQuote => 'Custom quote',
        };
    }

    /**
     * Suggested prompt chips shown in the customization chat.
     *
     * @return list<string>
     */
    public function suggestedPrompts(): array
    {
        return array_values(array_filter((array) ($this->chat_script['suggested_prompts'] ?? [])));
    }

    public function chatGreeting(): string
    {
        return (string) ($this->chat_script['greeting'] ?? "Hi! Tell us about the {$this->name} you need.");
    }

    public function chatNeedsPrompt(): string
    {
        return (string) ($this->chat_script['needs_prompt'] ?? 'What would you like us to focus on?');
    }

    protected static function booted(): void
    {
        static::saved(function (Service $service): void {
            if ($service->service_category_id !== null && ($service->wasRecentlyCreated || $service->wasChanged('service_category_id'))) {
                $service->categories()->syncWithoutDetaching([(int) $service->service_category_id]);
            }

            CatalogCache::flush();
        });
        static::deleted(fn () => CatalogCache::flush());
    }
}
