<?php

namespace Database\Seeders;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Seeder;

class ProblemCategorySeeder extends Seeder
{
    /**
     * Catálogo de categorias de problemas e respetivas especialidades.
     */
    public function run(): void
    {
        $catalog = [
            'Canalização' => [
                'description' => 'Problemas com água e esgotos.',
                'specialties' => [
                    'Fugas de água'                     => 'Reparação de fugas em canos e torneiras.',
                    'Desentupimentos'                   => 'Desentupimento de esgotos, sanitas e lava-loiças.',
                    'Instalação de sanitários'          => 'Montagem e substituição de loiças sanitárias.',
                    'Esquentadores e termoacumuladores' => 'Instalação e reparação de equipamentos de água quente.',
                ],
            ],
            'Eletricidade' => [
                'description' => 'Instalações e avarias elétricas.',
                'specialties' => [
                    'Quadros elétricos'     => 'Instalação e reparação de quadros.',
                    'Tomadas e iluminação'  => 'Instalação de tomadas, interruptores e pontos de luz.',
                    'Certificação elétrica' => 'Inspeção e certificação de instalações.',
                ],
            ],
            'Climatização' => [
                'description' => 'Aquecimento, arrefecimento e ventilação.',
                'specialties' => [
                    'Ar condicionado'     => 'Instalação e manutenção de ar condicionado.',
                    'Aquecimento central' => 'Caldeiras, radiadores e piso radiante.',
                    'Ventilação'          => 'Exaustores e sistemas de ventilação.',
                ],
            ],
            'Carpintaria' => [
                'description' => 'Trabalhos em madeira.',
                'specialties' => [
                    'Portas e janelas' => 'Reparação e montagem de portas e janelas.',
                    'Móveis por medida' => 'Construção e montagem de mobiliário.',
                ],
            ],
            'Pintura' => [
                'description' => 'Pintura e acabamentos de superfícies.',
                'specialties' => [
                    'Pintura interior'        => 'Pintura de paredes e tetos interiores.',
                    'Pintura exterior'        => 'Pintura de fachadas e muros.',
                    'Tratamento de humidades' => 'Tratamento de bolores, infiltrações e humidades.',
                ],
            ],
            'Eletrodomésticos' => [
                'description' => 'Reparação de eletrodomésticos.',
                'specialties' => [
                    'Máquinas de lavar' => 'Máquinas de lavar roupa e loiça.',
                    'Frio'              => 'Frigoríficos e arcas congeladoras.',
                ],
            ],
            'Limpezas' => [
                'description' => 'Serviços de limpeza.',
                'specialties' => [
                    'Limpeza doméstica' => 'Limpeza regular de habitações.',
                    'Limpeza pós-obra'  => 'Limpeza após obras e remodelações.',
                ],
            ],
            'Jardinagem' => [
                'description' => 'Manutenção de espaços exteriores.',
                'specialties' => [
                    'Manutenção de jardins' => 'Corte de relva, podas e limpeza.',
                    'Rega automática'       => 'Instalação e reparação de sistemas de rega.',
                ],
            ],
        ];

        foreach ($catalog as $categoryName => $data) {
            // 1. Cria (ou reaproveita) a categoria
            $category = ProblemCategory::firstOrCreate(
                ['name' => $categoryName],
                ['description' => $data['description']]
            );

            // 2. Cria as especialidades já ligadas a essa categoria
            foreach ($data['specialties'] as $specialtyName => $description) {
                $category->specialties()->firstOrCreate(
                    ['name' => $specialtyName],
                    ['description' => $description]
                );
            }
        }
    }
}