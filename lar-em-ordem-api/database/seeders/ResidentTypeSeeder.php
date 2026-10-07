<?php

namespace Database\Seeders;

use App\Models\User\ResidentType;
use Illuminate\Database\Seeder;

class ResidentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type' => 'Owner'],
            ['type' => 'Tenant'],
        ];

        foreach ($types as $type) {
            ResidentType::firstOrCreate($type);
        }
    }
}
