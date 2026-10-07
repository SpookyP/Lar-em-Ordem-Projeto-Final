<?php

namespace Database\Factories\Property;

use App\Models\Property\PropertyContract;
use App\Models\Property\Property;
use App\Models\User\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyContract>
 */
class PropertyContractFactory extends Factory
{
    protected $model = PropertyContract::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resident_id'      => Resident::factory(),
            'property_id'      => Property::factory(),
            'start_date'       => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'end_date'         => fake()->optional()->dateTimeBetween('+1 year', '+3 years')?->format('Y-m-d'),
            'resident_type_id' => fake()->numberBetween(1, 2),
            'is_active'        => true,
        ];
    }
}
