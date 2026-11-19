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

    private const STREETS = [
        'Rua de Santa Catarina', 'Rua do Almada', 'Avenida da Boavista', 'Rua Brito Capelo',
        'Avenida da Liberdade', 'Rua Augusta', 'Rua do Souto', 'Rua Ferreira Borges',
        'Avenida Lourenço Peixinho', 'Rua da Prata',
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
            'street'      => fake()->randomElement(self::STREETS),
            'postal_code' => fake()->numerify('####-###'),
            'door'        => 'Nº ' . fake()->numberBetween(1, 300),
            'fraction'    => fake()->optional()->randomElement(['1º Dto', '2º Esq', '3º Frt', 'R/C Dir']),
            'county'      => $county,
            'location'    => $county,
            'district'    => $district,
        ];
    }
}
