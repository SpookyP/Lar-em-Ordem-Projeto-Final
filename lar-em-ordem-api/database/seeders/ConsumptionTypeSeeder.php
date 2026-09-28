<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConsumptionType;

class ConsumptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['id' => 1, 'name' => 'Eletricidade', 'unit_of_measure' => 'kWh'],
            ['id' => 2, 'name' => 'Água', 'unit_of_measure' => 'm³'],
            ['id' => 3, 'name' => 'Gás', 'unit_of_measure' => 'kWh'],
        ];

        foreach ($types as $type) {
            ConsumptionType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
