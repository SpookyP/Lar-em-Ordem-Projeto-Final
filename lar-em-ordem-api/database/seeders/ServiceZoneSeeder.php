<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServiceZoneSeeder extends Seeder
{
    /**
     * Catálogo de zonas: distrito → concelho → freguesia (CAOP, via GEO API PT).
     * Os dados estão em database/data/service_zones.json — para atualizar: php artisan zones:import
     */
    public function run(): void
    {
        $now = now();
        $zones = json_decode(File::get(database_path('data/service_zones.json')), true);

        // insert em blocos; insertOrIgnore ignora o que já estiver inserido
        // por causa do índice único (district, county, location)
        foreach (array_chunk($zones, 500) as $chunk) {
            DB::table('service_zones')->insertOrIgnore(array_map(
                fn (array $zone) => $zone + ['created_at' => $now, 'updated_at' => $now],
                $chunk
            ));
        }
    }
}