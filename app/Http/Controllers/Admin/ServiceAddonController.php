<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AddonPricingMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAddonRequest;
use App\Models\ServiceAddon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceAddonController extends Controller
{
    public function index(): View
    {
        return view('admin.addons.index', [
            'addons' => ServiceAddon::query()->withCount('services')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.addons.form', [
            'addon' => new ServiceAddon(['pricing_method' => AddonPricingMethod::Hourly, 'price_cents' => 2500, 'is_active' => true]),
            'pricingMethods' => AddonPricingMethod::cases(),
        ]);
    }

    public function store(SaveAddonRequest $request): RedirectResponse
    {
        ServiceAddon::create($request->addonAttributes());

        return to_route('admin.addons.index')->with('status', 'Add-on created. Attach it to services from each service\'s edit page.');
    }

    public function edit(ServiceAddon $addon): View
    {
        return view('admin.addons.form', ['addon' => $addon, 'pricingMethods' => AddonPricingMethod::cases()]);
    }

    public function update(SaveAddonRequest $request, ServiceAddon $addon): RedirectResponse
    {
        $addon->update($request->addonAttributes());

        return to_route('admin.addons.index')->with('status', 'Add-on saved.');
    }

    public function destroy(ServiceAddon $addon): RedirectResponse
    {
        $addon->delete();

        return to_route('admin.addons.index')->with('status', 'Add-on deleted. Existing bookings keep their add-on details.');
    }
}
