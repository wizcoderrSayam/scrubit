<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ServiceCategory::query()->withCount(['services', 'children'])->get();

        return view('admin.categories.index', [
            'rows' => ServiceCategory::flattenTree(tree: $categories),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new ServiceCategory(['is_active' => true]),
            'parentOptions' => $this->parentOptions(new ServiceCategory),
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        ServiceCategory::create($request->categoryAttributes());

        return to_route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(ServiceCategory $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(SaveCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $category->update($request->categoryAttributes());

        return to_route('admin.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(ServiceCategory $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->withErrors(['category' => 'Move or delete sub-categories first.']);
        }

        if ($category->services()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Move or delete this category\'s services before deleting it.']);
        }

        $category->delete();

        return to_route('admin.categories.index')->with('status', 'Category deleted.');
    }

    /**
     * Indented full paths, excluding the category being edited and its descendants.
     *
     * @return array<int, string>
     */
    private function parentOptions(ServiceCategory $category): array
    {
        $except = $category->exists ? $category : null;

        return collect(ServiceCategory::flattenTree($except))
            ->mapWithKeys(fn (array $row): array => [
                $row['category']->id => str_repeat('— ', $row['depth']).$row['path'],
            ])
            ->all();
    }
}
