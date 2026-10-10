<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.testimonials.index', [
            'testimonials' => Testimonial::query()->with('service')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.testimonials.form', [
            'testimonial' => new Testimonial(['is_published' => true]),
            'services' => Service::query()->ordered()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::create($this->validated($request));

        return to_route('admin.testimonials.index')->with('status', 'Testimonial added.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.testimonials.form', [
            'testimonial' => $testimonial,
            'services' => Service::query()->ordered()->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));

        return to_route('admin.testimonials.index')->with('status', 'Testimonial saved.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return to_route('admin.testimonials.index')->with('status', 'Testimonial deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'service_id' => ['nullable', 'integer', Rule::exists(Service::class, 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        return [...$validated, 'sort_order' => (int) ($validated['sort_order'] ?? 0), 'is_published' => $request->boolean('is_published')];
    }
}
