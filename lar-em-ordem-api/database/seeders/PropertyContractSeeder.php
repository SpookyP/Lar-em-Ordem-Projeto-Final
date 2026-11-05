<?php

namespace Database\Seeders;

use App\Models\Property\PropertyContract;
use App\Models\Property\Property;
use App\Models\User\Resident;
use Illuminate\Database\Seeder;

class PropertyContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::whereDoesntHave('contracts', fn ($q) => $q->where('is_active', true))->get();
        $properties = Property::doesntHave('contracts')->get();

        foreach ($residents->zip($properties) as [$resident, $property]) {
            if (! $resident || ! $property) {
                break;
            }

            PropertyContract::factory()->create([
                'resident_id' => $resident->id,
                'property_id' => $property->id,
            ]);
        }
    }
}
