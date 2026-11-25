<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Zones with a real district and county; the locality gets a suffix so it doesn't repeat the seed parishes (CAOP).
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