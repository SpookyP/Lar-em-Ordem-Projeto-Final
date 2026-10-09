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
        $residents = Resident::all();
        $properties = Property::all();

        // Se não existirem registos prévios, cancela para evitar erros
        if ($residents->isEmpty() || $properties->isEmpty()) {
            return;
        }

        // Associa cada residente a uma propriedade através de um contrato
        foreach ($residents as $index => $resident) {
            $property = $properties->get($index) ?? $properties->random();

            PropertyContract::factory()->create([
                'resident_id' => $resident->id,
                'property_id' => $property->id,
            ]);
        }
    }
}
