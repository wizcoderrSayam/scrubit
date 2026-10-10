<?php

namespace App\Services\ImportExport;

use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Catalog rows for categories, services, and add-ons. Bookings are exported separately.
 */
final class CatalogExporter
{
    /**
     * @return iterable<int, list<string>>
     */
    public function rows(Dataset $dataset): iterable
    {
        return match ($dataset) {
            Dataset::Categories => $this->categories(),
            Dataset::Services => $this->services(),
            Dataset::Addons => $this->addons(),
            Dataset::Bookings => throw new InvalidArgumentException('Bookings are exported by the booking exporter.'),
        };
    }

    /**
     * @return iterable<int, list<string>>
     */
    private function categories(): iterable
    {
        $map = ServiceCategory::query()->get()->keyBy(fn (ServiceCategory $category): int => (int) $category->id);

        foreach (ServiceCategory::flattenTree(tree: $map) as $row) {
            $category = $row['category'];
            $parent = $category->parent_id !== null ? $map->get((int) $category->parent_id) : null;

            yield [
                $category->name,
                $category->slug,
                $parent?->slug ?? '',
                $category->description ?? '',
                $category->seo_title ?? '',
                $category->seo_description ?? '',
                (string) $category->sort_order,
                $category->is_active ? '1' : '0',
            ];
        }
    }

    /**
     * @return iterable<int, list<string>>
     */
    private function services(): iterable
    {
        $services = Service::query()->with('category')->ordered()->get();

        foreach ($services as $service) {
            yield [
                $service->name,
                $service->slug,
                $service->category->slug,
                $service->short_description,
                $service->description ?? '',
                implode('|', $service->inclusions ?? []),
                implode('|', $service->exclusions ?? []),
                $service->instructions ?? '',
                $service->pricing_method->value,
                $service->price_cents !== null ? Money::toDecimal($service->price_cents) : '',
                $service->hourly_rate_cents !== null ? Money::toDecimal($service->hourly_rate_cents) : '',
                $service->estimated_minutes !== null ? (string) $service->estimated_minutes : '',
                $service->allows_recurring ? '1' : '0',
                $service->seo_title ?? '',
                $service->seo_description ?? '',
                $service->is_active ? '1' : '0',
                (string) $service->sort_order,
            ];
        }
    }

    /**
     * @return iterable<int, list<string>>
     */
    private function addons(): iterable
    {
        $addons = ServiceAddon::query()
            ->with(['services' => fn ($query) => $query->orderBy('slug')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($addons as $addon) {
            yield [
                $addon->name,
                $addon->slug,
                $addon->description ?? '',
                $addon->pricing_method->value,
                $addon->price_cents !== null ? Money::toDecimal($addon->price_cents) : '',
                $addon->is_active ? '1' : '0',
                (string) $addon->sort_order,
                $addon->services->pluck('slug')->implode('|'),
            ];
        }
    }
}
