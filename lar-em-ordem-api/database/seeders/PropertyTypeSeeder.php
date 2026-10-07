<?php

namespace Database\Seeders;

use App\Models\Property\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type' => 'Apartment'],
            ['type' => 'House'],
        ];

        foreach ($types as $type) {
            PropertyType::firstOrCreate($type);
        }
    }
}
