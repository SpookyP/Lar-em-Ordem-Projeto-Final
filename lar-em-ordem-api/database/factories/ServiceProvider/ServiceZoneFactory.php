<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Zonas com distrito e concelho reais; a localidade tem um sufixo para não repetir as freguesias do seed (CAOP).
 *
 * @extends Factory<ServiceZone>
 */
class ServiceZoneFactory extends Factory
{
    private const LOCATIONS = [
        ['Porto', 'Porto'], ['Porto', 'Matosinhos'], ['Porto', 'Vila Nova de Gaia'], ['Lisboa', 'Lisboa'],
        ['Lisboa', 'Oeiras'], ['Braga', 'Braga'], ['Aveiro', 'Aveiro'], ['Coimbra', 'Coimbra'], ['Faro', 'Faro'],
    ];

    public function definition(): array
    {
        [$district, $county] = fake()->randomElement(self::LOCATIONS);

        return [
            'district' => $district,
            'county'   => $county,
            'location' => $county . ' (zona ' . fake()->unique()->numberBetween(1, 999) . ')',
        ];
    }
}