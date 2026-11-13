<?php

namespace Database\Factories\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Categorias realistas que não existem no ProblemCategorySeeder (para não repetir as do seed).
 *
 * @extends Factory<ProblemCategory>
 */
class ProblemCategoryFactory extends Factory
{
    private const CATALOG = [
        'Serralharia'           => 'Portões, grades, fechaduras e trabalhos em metal.',
        'Vidraçaria'            => 'Substituição de vidros e espelhos.',
        'Estores e persianas'   => 'Reparação e motorização de estores.',
        'Impermeabilização'     => 'Terraços, coberturas e infiltrações.',
        'Desinfestação'         => 'Controlo de pragas em casas e garagens.',
        'Gás'                   => 'Instalações e inspeções de gás.',
        'Telecomunicações'      => 'Cablagem, antenas e redes da casa.',
        'Alarmes e segurança'   => 'Instalação de alarmes e videovigilância.',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(array_keys(self::CATALOG));

        return [
            'name'        => $name,
            'description' => self::CATALOG[$name],
        ];
    }
}