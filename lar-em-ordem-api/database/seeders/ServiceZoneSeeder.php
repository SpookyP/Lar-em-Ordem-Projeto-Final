<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServiceZoneSeeder extends Seeder
{
    /**
     * Zone catalog: district → county → parish (CAOP, through the GEO API PT).
     * The data lives in database/data/service_zones.json; to update it run php artisan zones:import
     */
    public function run(): void
    {
        $now = now();
        $zones = json_decode(File::get(database_path('data/service_zones.json')), true);

        // insert in chunks; insertOrIgnore skips rows already there
        // because of the unique index (district, county, location)
        foreach (array_chunk($zones, 500) as $chunk) {
            DB::table('service_zones')->insertOrIgnore(array_map(
                fn (array $zone) => $zone + ['created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }
}