<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Every seeder is safe to re-run.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ServiceCatalogSeeder::class,
            ContentSeeder::class,
            AvailabilitySeeder::class,
        ]);
    }
}
