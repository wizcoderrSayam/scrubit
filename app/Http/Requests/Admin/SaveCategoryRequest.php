<?php

namespace App\Http\Requests\Admin;

use App\Models\ServiceCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveCategoryRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $parent = $this->input('parent_id');

        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'parent_id' => $parent === null || $parent === '' ? null : $parent,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...self::contentRules($this->route('category')?->id),
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(ServiceCategory::class, 'id'),
                $this->parentIsNotInTheSubtree(),
            ],
        ];
    }

    /**
     * Shared field rules for the category form and the spreadsheet importer.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function contentRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique(ServiceCategory::class, 'slug')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Reject parenting a category to itself or to one of its descendants.
     */
    private function parentIsNotInTheSubtree(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $category = $this->route('category');

            if (! $category instanceof ServiceCategory || $value === null || $value === '') {
                return;
            }

            if ((int) $value === $category->id) {
                $fail('A category cannot be its own parent.');

                return;
            }

            if (in_array((int) $value, $category->descendantIds(), true)) {
                $fail('A category cannot be moved under one of its sub-categories.');
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryAttributes(): array
    {
        return [
            ...$this->safe()->except(['is_active', 'sort_order']),
            'sort_order' => (int) $this->validated('sort_order', 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
