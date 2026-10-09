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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'street'      => fake()->streetName(),
            'postal_code' => fake()->numerify('####-###'),
            'door'        => fake()->optional()->randomElement(['1º Dto', '2º Esq', '3º Cnt', 'R/C Dir']),
            'county'      => fake()->city(),
            'location'    => fake()->city(),
            'district'    => fake()->randomElement(['Porto', 'Lisboa', 'Braga', 'Aveiro', 'Faro']),
            ];
    }
}
