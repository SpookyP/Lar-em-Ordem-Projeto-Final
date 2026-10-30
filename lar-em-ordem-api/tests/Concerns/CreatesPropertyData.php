<?php

namespace Tests\Concerns;

use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\User\Resident;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds a property and the contract that links a resident to it, the way ownership is checked in the app
 * (a resident owns a property while they have an active contract on it). Needs the seeded lookup tables.
 */
trait CreatesPropertyData
{
    protected function makeProperty(string $district = 'Porto'): Property
    {
        $type = PropertyType::first() ?? PropertyType::factory()->create();
        $typology = PropertyTypology::first() ?? PropertyTypology::factory()->create();

        $address = Address::unguarded(fn () => Address::create([
            'street'      => 'Rua de Teste',
            'postal_code' => '4000-001',
            'door'        => '1',
            'fraction'    => '1º A',
            'county'      => $district,
            'location'    => $district,
            'district'    => $district,
        ]));

        return Property::unguarded(fn () => Property::create([
            'property_type_id'     => $type->id,
            'property_typology_id' => $typology->id,
            'address_id'           => $address->id,
            'condominium_id'       => null,
            'area'                 => 80,
        ]));
    }

    protected function giveContract(User $user, Property $property, bool $active = true): void
    {
        $resident = Resident::unguarded(fn () => Resident::firstOrCreate(
            ['user_id' => $user->id],
            ['nif' => fake()->numerify('2########'), 'is_active' => true]
        ));

        $residentTypeId = DB::table('resident_types')->value('id')
            ?? DB::table('resident_types')->insertGetId(['type' => 'Proprietário']);

        PropertyContract::firstOrCreate(
            ['resident_id' => $resident->id, 'property_id' => $property->id],
            [
                'start_date'       => now()->subYear()->toDateString(),
                'end_date'         => null,
                'resident_type_id' => $residentTypeId,
                'is_active'        => $active,
            ]
        );
    }
}