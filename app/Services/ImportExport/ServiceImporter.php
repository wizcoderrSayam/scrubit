<?php

namespace App\Services\ImportExport;

use App\Http\Requests\Admin\SaveServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Money;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Validates every service row, then upserts by slug in one transaction. Any error imports nothing.
 */
final class ServiceImporter
{
    public function __construct(private SpreadsheetReader $reader, private CatalogExporter $exporter) {}

    public function import(string $path, string $extension): ImportResult
    {
        $rows = $this->reader->rows($path, $extension, $this->exporter->headers());
        $errors = [];
        $this->flagDuplicateSlugs($rows, $errors);
        $prepared = [];
        $categoryIds = ServiceCategory::query()->pluck('id', 'slug');

        foreach ($rows as $row) {
            $line = (int) $row['__row'];
            $data = $this->optionalText($row, ['description', 'inclusions', 'exclusions', 'instructions', 'price', 'hourly_rate', 'estimated_minutes', 'seo_title', 'seo_description']);
            $data['sort_order'] = ($row['sort_order'] ?? '') === '' ? '0' : $row['sort_order'];
            $data['is_active'] = ($row['is_active'] ?? '') === '' ? '0' : $row['is_active'];
            $data['allows_recurring'] = ($row['allows_recurring'] ?? '') === '' ? '0' : $row['allows_recurring'];
            $existing = Service::withTrashed()->where('slug', (string) ($data['slug'] ?? ''))->first();

            foreach ($this->validate($data, [
                ...SaveServiceRequest::contentRules($existing?->id, $data['pricing_method'] ?? null),
                'category_slug' => ['required', 'string', $this->categorySlugRule($categoryIds)],
            ], (new SaveServiceRequest)->messages()) as $message) {
                $errors[$line][] = $message;
            }

            $prepared[] = $data;
        }

        if ($errors !== []) {
            ksort($errors);

            return new ImportResult(errors: $errors);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($prepared, $categoryIds, &$created, &$updated): void {
            foreach ($prepared as $data) {
                $assignedCategoryIds = array_map(
                    fn (string $slug): int => (int) $categoryIds[$slug],
                    array_values(array_unique($this->pipeList($data['category_slug']))),
                );
                $attributes = [
                    'service_category_id' => $assignedCategoryIds[0],
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'] ?? null,
                    'inclusions' => $this->pipeList($data['inclusions'] ?? null),
                    'exclusions' => $this->pipeList($data['exclusions'] ?? null),
                    'instructions' => $data['instructions'] ?? null,
                    'pricing_method' => $data['pricing_method'],
                    'price_cents' => filled($data['price'] ?? null) ? Money::parse($data['price']) : null,
                    'hourly_rate_cents' => filled($data['hourly_rate'] ?? null) ? Money::parse($data['hourly_rate']) : null,
                    'estimated_minutes' => $data['estimated_minutes'] ?? null,
                    'allows_recurring' => filter_var($data['allows_recurring'], FILTER_VALIDATE_BOOLEAN),
                    'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                    'is_active' => filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN),
                    'sort_order' => (int) $data['sort_order'],
                ];

                $service = Service::withTrashed()->where('slug', $data['slug'])->first();

                if ($service === null) {
                    $createdService = Service::query()->create($attributes);
                    $createdService->syncCategories($assignedCategoryIds);
                    $created++;

                    continue;
                }

                $restored = $service->trashed();

                if ($restored) {
                    $service->restore();
                }

                $service->fill($attributes);

                if ($service->isDirty()) {
                    $service->save();
                }

                $categoryChanges = $service->syncCategories($assignedCategoryIds);

                if ($restored || $service->wasChanged() || $categoryChanges['attached'] !== [] || $categoryChanges['detached'] !== []) {
                    $updated++;
                }
            }
        });

        return new ImportResult($created, $updated);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  array<int, list<string>>  $errors
     */
    private function flagDuplicateSlugs(array $rows, array &$errors): void
    {
        $seen = [];

        foreach ($rows as $row) {
            $slug = $row['slug'] ?? '';

            if ($slug === '') {
                continue;
            }

            $line = (int) $row['__row'];

            if (isset($seen[$slug])) {
                $errors[$line][] = 'This slug is repeated in the file.';
            }

            $seen[$slug] = $line;
        }
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function optionalText(array $row, array $keys): array
    {
        $data = $row;

        foreach ($keys as $key) {
            if (! isset($data[$key]) || $data[$key] === '') {
                $data[$key] = null;
            }
        }

        unset($data['__row']);

        return $data;
    }

    /**
     * Accept one category slug, or several separated by |.
     *
     * @param  Collection<string, int>  $categoryIds
     */
    private function categorySlugRule(Collection $categoryIds): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($categoryIds): void {
            $slugs = $this->pipeList($value);

            if ($slugs === []) {
                $fail('A category slug is required.');

                return;
            }

            foreach ($slugs as $slug) {
                if (! $categoryIds->has($slug)) {
                    $fail("The category {$slug} does not exist.");
                }
            }
        };
    }

    /**
     * @return list<string>
     */
    private function pipeList(mixed $value): array
    {
        return array_values(array_filter(
            array_map(trim(...), explode('|', (string) $value)),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return list<string>
     */
    private function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        return $validator->fails() ? $validator->errors()->all() : [];
    }
}
