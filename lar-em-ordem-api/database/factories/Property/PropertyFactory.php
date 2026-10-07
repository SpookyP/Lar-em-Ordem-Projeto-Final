<?php

namespace Database\Factories\Property;

use App\Models\Property\Property;
use App\Models\Property\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_type_id'     => fake()->numberBetween(1, 2),
            'property_typology_id' => fake()->numberBetween(1, 3),
            'address_id'           => Address::factory(),
            'condominium_id'       => null,
            'area'                 => fake()->numberBetween(3500, 25000),
            'fraction'             => fake()->optional()->randomElement(['A', 'B', 'C', 'D', 'E']),
        ];
    }
}
