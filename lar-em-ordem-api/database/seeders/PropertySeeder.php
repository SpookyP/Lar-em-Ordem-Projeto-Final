<?php

namespace Database\Seeders;

use App\Models\Property\Property;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Property::factory(30)->create();
    }
}
