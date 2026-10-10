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
        return view('admin.categories.index', [
            'categories' => ServiceCategory::query()->withCount('services')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new ServiceCategory(['is_active' => true])]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        ServiceCategory::create($request->categoryAttributes());

        return to_route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(ServiceCategory $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(SaveCategoryRequest $request, ServiceCategory $category): RedirectResponse
    {
        $category->update($request->categoryAttributes());

        return to_route('admin.categories.index')->with('status', 'Category saved.');
    }

    public function destroy(ServiceCategory $category): RedirectResponse
    {
        if ($category->services()->withTrashed()->exists()) {
            return back()->withErrors(['category' => 'Move or delete this category\'s services before deleting it.']);
        }

        $category->delete();

        return to_route('admin.categories.index')->with('status', 'Category deleted.');
    }
}
