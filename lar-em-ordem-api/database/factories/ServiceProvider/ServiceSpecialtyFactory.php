<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\ServiceSpecialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Especialidades realistas que não existem no ProblemCategorySeeder.
 *
 * @extends Factory<ServiceSpecialty>
 */
class ServiceSpecialtyFactory extends Factory
{
    private const CATALOG = [
        'Reparação de fechaduras'   => 'Substituição de canhões e reparação de fechaduras.',
        'Substituição de vidros'    => 'Vidros partidos em janelas e portas.',
        'Motorização de estores'    => 'Instalação de motores em estores existentes.',
        'Impermeabilização de terraços' => 'Tratamento de terraços e coberturas.',
        'Controlo de pragas'        => 'Desinfestação de baratas, ratos e térmitas.',
        'Inspeção de gás'           => 'Inspeção periódica das instalações de gás.',
        'Instalação de antenas'     => 'Antenas de televisão e parabólicas.',
        'Videovigilância'           => 'Instalação de câmaras e gravação.',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(array_keys(self::CATALOG));

        return [
            'problem_category_id' => ProblemCategory::factory(),
            'name'                => $name,
            'description'         => self::CATALOG[$name],
        ];
    }
}