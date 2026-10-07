<?php

namespace Database\Seeders;

use App\Models\Property\Property;
use App\Models\User\Resident;
use App\Models\Invoice\Invoice;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::all();
        $properties = Property::all();
        if ($residents->isEmpty() || $properties->isEmpty()) {
            return;
        }
        foreach ($residents as $index => $resident) {
            $property = $properties->get($index) ?? $properties->random();

            Invoice::factory()->count(25)->create([
                'user_id' => $resident->user_id,
                'property_id' => $property->id,
            ]);
        }
    }
}
