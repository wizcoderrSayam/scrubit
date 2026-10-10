<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxRateController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.tax', ['rates' => TaxRate::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        TaxRate::create($this->validated($request));

        return to_route('admin.settings.tax.index')->with('status', 'Tax rate added.');
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $taxRate->update($this->validated($request));

        return to_route('admin.settings.tax.index')->with('status', 'Tax rate saved.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $taxRate->delete();

        return to_route('admin.settings.tax.index')->with('status', 'Tax rate deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'rate' => ['required', 'string', 'regex:/^\d{1,2}(\.\d{1,2})?$/'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], ['rate.regex' => 'Enter the rate as a percentage, e.g. 5 or 7.25.']);

        [$whole, $fraction] = array_pad(explode('.', $validated['rate']), 2, '0');

        return [
            'name' => $validated['name'],
            'rate_bps' => ((int) $whole * 100) + (int) str_pad($fraction, 2, '0'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
