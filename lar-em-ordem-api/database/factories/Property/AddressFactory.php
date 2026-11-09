<?php

namespace Database\Factories\Property;

use App\Models\Property\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public const LOCATIONS = [
        ['Porto', 'Matosinhos'],
        ['Porto', 'Porto'],
        ['Porto', 'Vila Nova de Gaia'],
        ['Lisboa', 'Lisboa'],
        ['Lisboa', 'Oeiras'],
        ['Braga', 'Braga'],
        ['Aveiro', 'Aveiro'],
        ['Coimbra', 'Coimbra'],
    ];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$district, $county] = fake()->randomElement(self::LOCATIONS);
        return [
            'street'      => fake()->streetName(),
            'postal_code' => fake()->numerify('####-###'),
            'door'        => 'Nº ' . fake()->numberBetween(1, 300),
            'fraction'    => fake()->optional()->randomElement(['1º Dto', '2º Esq', '3º Frt', 'R/C Dir']),
            'county'      => $county,
            'location'    => $county,
            'district'    => $district,
        ];
    }
}
