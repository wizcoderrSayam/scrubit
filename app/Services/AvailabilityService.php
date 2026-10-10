<?php

namespace App\Services;

use App\Enums\TimeWindow;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Decides which preferred dates and time windows customers may request.
 */
class AvailabilityService
{
    public function __construct(private SettingsService $settings) {}

    public function earliestDate(): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays((int) $this->settings->get('booking.min_lead_days', 1));
    }

    public function latestDate(): CarbonImmutable
    {
        return CarbonImmutable::today()->addDays((int) $this->settings->get('booking.max_advance_days', 90));
    }

    /**
     * Explain why a date/window cannot be requested, or null when it is bookable.
     */
    public function unavailableReason(CarbonImmutable $date, TimeWindow $window): ?string
    {
        $date = $date->startOfDay();

        if ($date->lt($this->earliestDate())) {
            return 'Please choose a date on or after '.$this->earliestDate()->format('F j, Y').'.';
        }

        if ($date->gt($this->latestDate())) {
            return 'Please choose a date before '.$this->latestDate()->format('F j, Y').'.';
        }

        if (AvailabilityException::query()->whereDate('date', $date)->exists()) {
            return 'We are not taking bookings on '.$date->format('F j, Y').'. Please choose another date.';
        }

        $rule = $this->rules()->get($date->dayOfWeek);

        if ($rule !== null && ! $rule->is_open) {
            return 'We are closed on '.$date->format('l').'s. Please choose another day.';
        }

        if ($rule !== null && ! in_array($window->value, (array) $rule->windows, true)) {
            return 'That time window is not available on '.$date->format('l').'s.';
        }

        return null;
    }

    public function isBookable(CarbonImmutable $date, TimeWindow $window): bool
    {
        return $this->unavailableReason($date, $window) === null;
    }

    /**
     * Weekday rules keyed by weekday (0 = Sunday). Days with no rule are open for every window.
     *
     * @return Collection<int, AvailabilityRule>
     */
    public function rules(): Collection
    {
        return AvailabilityRule::query()->get()->keyBy('weekday');
    }

    /**
     * Upcoming closed dates for display in the date picker.
     *
     * @return list<string>
     */
    public function closedDates(): array
    {
        return AvailabilityException::query()
            ->whereBetween('date', [$this->earliestDate()->toDateString(), $this->latestDate()->toDateString()])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->all();
    }

    /**
     * Weekdays (0-6) that are closed entirely.
     *
     * @return list<int>
     */
    public function closedWeekdays(): array
    {
        return $this->rules()->filter(fn (AvailabilityRule $rule) => ! $rule->is_open)->keys()->values()->all();
    }
}
