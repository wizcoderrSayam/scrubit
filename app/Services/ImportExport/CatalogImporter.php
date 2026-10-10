<?php

namespace App\Services\ImportExport;

use App\Enums\AddonPricingMethod;
use App\Http\Requests\Admin\SaveAddonRequest;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Http\Requests\Admin\SaveServiceRequest;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Validates every row, then upserts by slug in one transaction. Any error imports nothing and never deletes rows.
 */
final class CatalogImporter
{
    public function __construct(private SpreadsheetReader $reader) {}

    public function import(Dataset $dataset, string $path, string $extension): ImportResult
    {
        if (! $dataset->importable()) {
            throw new ImportException('Bookings can be exported, not imported.');
        }

        $rows = $this->reader->rows($path, $extension, $dataset->headers());

        return match ($dataset) {
            Dataset::Categories => $this->importCategories($rows),
            Dataset::Services => $this->importServices($rows),
            Dataset::Addons => $this->importAddons($rows),
            Dataset::Bookings => throw new ImportException('Bookings can be exported, not imported.'),
        };
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function importCategories(array $rows): ImportResult
    {
        $errors = [];
        $this->flagDuplicateSlugs($rows, $errors);
        $prepared = [];

        foreach ($rows as $row) {
            $line = (int) $row['__row'];
            $data = $this->optionalText($row, ['description', 'seo_title', 'seo_description', 'parent_slug']);
            $data['sort_order'] = $this->sortOrder($row);
            $data['is_active'] = $this->booleanCell($row, 'is_active');
            $existing = ServiceCategory::query()->where('slug', (string) ($data['slug'] ?? ''))->first();
            $this->addErrors($errors, $line, $this->validate($data, [
                ...SaveCategoryRequest::contentRules($existing?->id),
                'parent_slug' => ['nullable', 'string', 'max:140', 'alpha_dash'],
            ]));
            $prepared[] = $data + ['__line' => $line];
        }

        $this->categoryGraphErrors($prepared, $errors);

        if ($errors !== []) {
            ksort($errors);

            return new ImportResult(errors: $errors);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($prepared, &$created, &$updated): void {
            $idsBySlug = ServiceCategory::query()->pluck('id', 'slug')->all();

            foreach ($this->categoryOrder($prepared) as $data) {
                $parentSlug = $data['parent_slug'] ?? null;
                $attributes = [
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'parent_id' => filled($parentSlug) ? (int) $idsBySlug[$parentSlug] : null,
                    'description' => $data['description'] ?? null,
                    'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                    'sort_order' => (int) $data['sort_order'],
                    'is_active' => $this->truthy($data['is_active']),
                ];

                if (isset($idsBySlug[$data['slug']])) {
                    $category = ServiceCategory::query()->findOrFail($idsBySlug[$data['slug']]);
                    $category->fill($attributes);

                    if ($category->isDirty()) {
                        $category->save();
                        $updated++;
                    }

                    continue;
                }

                $category = ServiceCategory::query()->create($attributes);
                $idsBySlug[$category->slug] = $category->id;
                $created++;
            }
        });

        return new ImportResult($created, $updated);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function importServices(array $rows): ImportResult
    {
        $errors = [];
        $this->flagDuplicateSlugs($rows, $errors);
        $prepared = [];

        foreach ($rows as $row) {
            $line = (int) $row['__row'];
            $data = $this->optionalText($row, ['description', 'inclusions', 'exclusions', 'instructions', 'price', 'hourly_rate', 'estimated_minutes', 'seo_title', 'seo_description']);
            $data['sort_order'] = $this->sortOrder($row);
            $data['is_active'] = $this->booleanCell($row, 'is_active');
            $data['allows_recurring'] = $this->booleanCell($row, 'allows_recurring');
            $existing = Service::withTrashed()->where('slug', (string) ($data['slug'] ?? ''))->first();
            $this->addErrors($errors, $line, $this->validate($data, [
                ...SaveServiceRequest::contentRules($existing?->id, $data['pricing_method'] ?? null),
                'category_slug' => ['required', 'string', Rule::exists(ServiceCategory::class, 'slug')],
            ], (new SaveServiceRequest)->messages()));
            $prepared[] = $data + ['__line' => $line];
        }

        if ($errors !== []) {
            ksort($errors);

            return new ImportResult(errors: $errors);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($prepared, &$created, &$updated): void {
            $categoryIds = ServiceCategory::query()->pluck('id', 'slug');

            foreach ($prepared as $data) {
                $attributes = [
                    'service_category_id' => (int) $categoryIds[$data['category_slug']],
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
                    'allows_recurring' => $this->truthy($data['allows_recurring']),
                    'seo_title' => $data['seo_title'] ?? null,
                    'seo_description' => $data['seo_description'] ?? null,
                    'is_active' => $this->truthy($data['is_active']),
                    'sort_order' => (int) $data['sort_order'],
                ];

                $service = Service::withTrashed()->where('slug', $data['slug'])->first();

                if ($service === null) {
                    Service::query()->create($attributes);
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

                if ($restored || $service->wasChanged()) {
                    $updated++;
                }
            }
        });

        return new ImportResult($created, $updated);
    }

    /**
     * @param  list<array<string, string>>  $rows
     */
    private function importAddons(array $rows): ImportResult
    {
        $errors = [];
        $this->flagDuplicateSlugs($rows, $errors);
        $prepared = [];

        foreach ($rows as $row) {
            $line = (int) $row['__row'];
            $data = $this->optionalText($row, ['description', 'price']);
            $data['sort_order'] = $this->sortOrder($row);
            $data['is_active'] = $this->booleanCell($row, 'is_active');
            $existing = ServiceAddon::query()->where('slug', (string) ($data['slug'] ?? ''))->first();
            $this->addErrors($errors, $line, $this->validate($data, SaveAddonRequest::contentRules($existing?->id, $data['pricing_method'] ?? null), (new SaveAddonRequest)->messages()));

            $slugs = $this->pipeList($row['service_slugs'] ?? null);
            $known = $slugs === []
                ? collect()
                : Service::query()->whereIn('slug', $slugs)->pluck('id', 'slug');

            foreach ($slugs as $slug) {
                if (! $known->has($slug)) {
                    $errors[$line][] = "The service slug {$slug} does not match a service.";
                }
            }

            $prepared[] = $data + [
                '__line' => $line,
                '__service_ids' => array_values(array_unique(array_map(
                    fn (string $slug): int => (int) $known->get($slug),
                    array_values(array_filter($slugs, fn (string $slug): bool => $known->has($slug))),
                ))),
            ];
        }

        if ($errors !== []) {
            ksort($errors);

            return new ImportResult(errors: $errors);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($prepared, &$created, &$updated): void {
            foreach ($prepared as $data) {
                $quoted = $data['pricing_method'] === AddonPricingMethod::Quote->value;
                $attributes = [
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'pricing_method' => $data['pricing_method'],
                    'price_cents' => $quoted || blank($data['price'] ?? null) ? null : Money::parse($data['price']),
                    'sort_order' => (int) $data['sort_order'],
                    'is_active' => $this->truthy($data['is_active']),
                ];
                $addon = ServiceAddon::query()->where('slug', $data['slug'])->first();

                if ($addon === null) {
                    $addon = ServiceAddon::query()->create($attributes);
                    $addon->services()->sync($data['__service_ids']);
                    $created++;

                    continue;
                }

                $addon->fill($attributes);
                $dirty = $addon->isDirty();

                if ($dirty) {
                    $addon->save();
                }

                $changes = $addon->services()->sync($data['__service_ids']);
                $pivotChanged = $changes['attached'] !== [] || $changes['detached'] !== [] || $changes['updated'] !== [];

                if ($dirty || $pivotChanged) {
                    $updated++;
                }
            }
        });

        return new ImportResult($created, $updated);
    }

    /**
     * @param  list<array<string, mixed>>  $prepared
     * @param  array<int, list<string>>  $errors
     */
    private function categoryGraphErrors(array $prepared, array &$errors): void
    {
        $bySlug = [];

        foreach ($prepared as $data) {
            $slug = (string) ($data['slug'] ?? '');

            if ($slug !== '') {
                $bySlug[$slug] = $data;
            }
        }

        $dbSlugs = array_fill_keys(ServiceCategory::query()->pluck('slug')->all(), true);

        foreach ($bySlug as $slug => $data) {
            $parent = $data['parent_slug'] ?? null;

            if (! filled($parent)) {
                continue;
            }

            if ($parent === $slug) {
                $errors[$data['__line']][] = 'A category cannot be its own parent.';

                continue;
            }

            if (! isset($bySlug[$parent]) && ! isset($dbSlugs[$parent])) {
                $errors[$data['__line']][] = 'The parent slug does not match a category.';

                continue;
            }

            $seen = [$slug => true];
            $cursor = $parent;

            while (isset($bySlug[$cursor])) {
                if (isset($seen[$cursor])) {
                    $errors[$data['__line']][] = 'This parent assignment creates a cycle.';

                    break;
                }

                $seen[$cursor] = true;
                $next = $bySlug[$cursor]['parent_slug'] ?? null;

                if (! filled($next) || $next === $cursor) {
                    break;
                }

                $cursor = $next;
            }
        }
    }

    /**
     * Parents in this file are returned before the rows that depend on them.
     *
     * @param  list<array<string, mixed>>  $prepared
     * @return list<array<string, mixed>>
     */
    private function categoryOrder(array $prepared): array
    {
        $bySlug = [];

        foreach ($prepared as $data) {
            $bySlug[$data['slug']] = $data;
        }

        $ordered = [];
        $done = [];

        $walk = function (string $slug) use (&$walk, &$ordered, &$done, $bySlug): void {
            if (isset($done[$slug])) {
                return;
            }

            $done[$slug] = true;
            $parent = $bySlug[$slug]['parent_slug'] ?? null;

            if (filled($parent) && isset($bySlug[$parent])) {
                $walk($parent);
            }

            $ordered[] = $bySlug[$slug];
        };

        foreach (array_keys($bySlug) as $slug) {
            $walk($slug);
        }

        return $ordered;
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
     * @param  array<string, string>  $row
     */
    private function sortOrder(array $row): string
    {
        return ($row['sort_order'] ?? '') === '' ? '0' : $row['sort_order'];
    }

    /**
     * @param  array<string, string>  $row
     */
    private function booleanCell(array $row, string $key): string
    {
        return ($row[$key] ?? '') === '' ? '0' : $row[$key];
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
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

    /**
     * @param  array<int, list<string>>  $errors
     * @param  list<string>  $messages
     */
    private function addErrors(array &$errors, int $line, array $messages): void
    {
        foreach ($messages as $message) {
            $errors[$line][] = $message;
        }
    }
}
