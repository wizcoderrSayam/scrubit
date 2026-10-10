<?php

namespace Database\Seeders;

use App\Enums\TimeWindow;
use App\Models\AvailabilityRule;
use Illuminate\Database\Seeder;

/**
 * Opens every day and window by default. The business sets its real hours in the admin.
 */
class AvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(0, 6) as $weekday) {
            AvailabilityRule::query()->firstOrCreate(['weekday' => $weekday], [
                'is_open' => true,
                'windows' => array_column(TimeWindow::cases(), 'value'),
            ]);
        }
    }
}
