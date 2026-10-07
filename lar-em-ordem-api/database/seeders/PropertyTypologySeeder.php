<?php

namespace Database\Seeders;

use App\Models\Property\PropertyTypology;
use Illuminate\Database\Seeder;

class PropertyTypologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typologies = [
            ['typology' => 'T0'],
            ['typology' => 'T1'],
            ['typology' => 'T2'],
            ['typology' => 'T3'],
            ['typology' => 'T4'],
            ['typology' => 'T5'],
            ['typology' => 'T6'],
            ['typology' => 'T7'],
            ['typology' => 'T8'],
            ['typology' => 'T9'],
        ];

        foreach ($typologies as $typology) {
            PropertyTypology::firstOrCreate($typology);
        }
    }
}
