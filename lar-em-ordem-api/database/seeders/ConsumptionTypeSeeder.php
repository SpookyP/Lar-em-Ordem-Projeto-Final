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
        ConsumptionType::insert([
            ['id' => 1, 'name' => 'Eletricidade', 'unit' => 'kWh'],
            ['id' => 2, 'name' => 'Água', 'unit' => 'm3'],
            ['id' => 3, 'name' => 'Gás', 'unit' => 'kWh'],
        ]);
    }
}
