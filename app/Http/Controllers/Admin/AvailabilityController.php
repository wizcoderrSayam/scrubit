<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TimeWindow;
use App\Http\Controllers\Controller;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function edit(): View
    {
        $rules = AvailabilityRule::query()->get()->keyBy('weekday');

        return view('admin.settings.availability', [
            'days' => collect(range(0, 6))->map(fn (int $weekday) => [
                'weekday' => $weekday,
                'name' => AvailabilityRule::WEEKDAYS[$weekday],
                'is_open' => $rules->get($weekday)?->is_open ?? true,
                'windows' => $rules->get($weekday)?->windows ?? array_column(TimeWindow::cases(), 'value'),
            ]),
            'windows' => TimeWindow::cases(),
            'exceptions' => AvailabilityException::query()->whereDate('date', '>=', today())->orderBy('date')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_open' => ['nullable', 'boolean'],
            'days.*.windows' => ['nullable', 'array'],
            'days.*.windows.*' => [Rule::enum(TimeWindow::class)],
        ]);

        foreach (range(0, 6) as $weekday) {
            $day = $validated['days'][$weekday] ?? [];
            AvailabilityRule::query()->updateOrCreate(['weekday' => $weekday], [
                'is_open' => (bool) ($day['is_open'] ?? false),
                'windows' => array_values(array_unique($day['windows'] ?? [])),
            ]);
        }

        return to_route('admin.settings.availability.edit')->with('status', 'Weekly availability saved.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', Rule::unique(AvailabilityException::class, 'date')],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        AvailabilityException::create($validated);

        return to_route('admin.settings.availability.edit')->with('status', 'Closed date added.');
    }

    public function destroyException(AvailabilityException $exception): RedirectResponse
    {
        $exception->delete();

        return to_route('admin.settings.availability.edit')->with('status', 'Closed date removed.');
    }
}
