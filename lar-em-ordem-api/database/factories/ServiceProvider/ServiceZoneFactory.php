<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceZone>
 */
class ServiceZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district' => fake()->randomElement(['Porto', 'Lisboa', 'Braga', 'Aveiro', 'Faro']),
            'county'   => fake()->unique()->city(),
            'location' => fake()->city(),
        ];
    }
}