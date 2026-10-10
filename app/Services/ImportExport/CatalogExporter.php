<?php

namespace App\Services\ImportExport;

use App\Models\Service;
use App\Support\Money;

/**
 * Rows for the services spreadsheet download.
 */
final class CatalogExporter
{
    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'name', 'slug', 'category_slug', 'short_description', 'description', 'inclusions', 'exclusions',
            'instructions', 'pricing_method', 'price', 'hourly_rate', 'estimated_minutes', 'allows_recurring',
            'seo_title', 'seo_description', 'is_active', 'sort_order',
        ];
    }

    /**
     * @return iterable<int, list<string>>
     */
    public function rows(): iterable
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
}
