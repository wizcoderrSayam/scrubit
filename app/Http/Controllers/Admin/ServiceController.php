<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServicePricingMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveServiceRequest;
use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceCategory;
use App\Services\ImageUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private ImageUploader $images) {}

    public function index(Request $request): View
    {
        $services = Service::query()
            ->with('category')
            ->withCount('bookings')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.services.index', ['services' => $services]);
    }

    public function create(): View
    {
        return view('admin.services.form', $this->formData(new Service([
            'pricing_method' => ServicePricingMethod::CustomQuote,
            'is_active' => false,
        ])));
    }

    public function store(SaveServiceRequest $request): RedirectResponse
    {
        $service = DB::transaction(function () use ($request): Service {
            $service = Service::create($request->serviceAttributes());
            $this->syncRelations($service, $request);

            return $service;
        });

        return to_route('admin.services.edit', $service)->with('status', 'Service created.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', $this->formData($service->load(['images', 'addons'])));
    }

    public function update(SaveServiceRequest $request, Service $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $service): void {
            $service->update($request->serviceAttributes());
            $this->syncRelations($service, $request);
        });

        return to_route('admin.services.edit', $service)->with('status', 'Service saved.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back()->with('status', $service->is_active ? 'Service published.' : 'Service unpublished.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->update(['is_active' => false]);
        $service->delete();

        return to_route('admin.services.index')->with('status', 'Service deleted. Existing bookings keep their service details.');
    }

    private function syncRelations(Service $service, SaveServiceRequest $request): void
    {
        $service->addons()->sync($request->validated('addons', []));

        if ($request->boolean('remove_featured_image') && $service->featured_image) {
            $this->images->delete($service->featured_image);
            $service->forceFill(['featured_image' => null])->save();
        }

        if ($request->hasFile('featured_image')) {
            $previous = $service->featured_image;
            $service->forceFill(['featured_image' => $this->images->store($request->file('featured_image'))])->save();
            $this->images->delete($previous);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Service $service): array
    {
        return [
            'service' => $service,
            'categories' => ServiceCategory::query()->ordered()->get(),
            'addons' => ServiceAddon::query()->orderBy('sort_order')->orderBy('name')->get(),
            'pricingMethods' => ServicePricingMethod::cases(),
        ];
    }
}
