<?php

namespace App\Models;

use App\Enums\AddonPricingMethod;
use App\Support\CatalogCache;
use App\Support\Money;
use Database\Factories\ServiceAddonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'pricing_method', 'price_cents', 'is_active', 'sort_order'])]
class ServiceAddon extends Model
{
    /** @use HasFactory<ServiceAddonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_method' => AddonPricingMethod::class,
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isPriced(): bool
    {
        return $this->pricing_method !== AddonPricingMethod::Quote && $this->price_cents !== null;
    }

    public function priceLabel(string $currency = 'CAD'): string
    {
        if (! $this->isPriced()) {
            return 'Quoted';
        }

        $price = Money::formatCompact($this->price_cents, $currency);

        return $this->pricing_method === AddonPricingMethod::Hourly ? "{$price}/hr" : $price;
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }
}
