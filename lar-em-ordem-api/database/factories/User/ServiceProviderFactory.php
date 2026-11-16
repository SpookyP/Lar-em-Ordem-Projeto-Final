<?php

namespace Database\Factories\User;

use App\Models\User\ServiceProvider;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ServiceProvider>
 */
class ServiceProviderFactory extends Factory
{
    /** Empresas de serviços para a casa (fictícias): nome => descrição. */
    private const CATALOG = [
        'Canalizações Ribeiro'         => 'Reparação de fugas, desentupimentos e substituição de canalizações.',
        'Eletro Rápido'                => 'Avarias elétricas, quadros e instalação de tomadas e iluminação.',
        'Casa em Dia Reparações'       => 'Pequenas reparações de canalização, eletricidade e humidades.',
        'Mãos à Obra Serviços'         => 'Manutenção geral da casa, com orçamento sem compromisso.',
        'Lar Seguro Manutenção'        => 'Assistência técnica ao domicílio em 24 horas.',
        'Técnicos do Norte'            => 'Equipa de técnicos certificados para reparações urgentes.',
        'Repara Já'                    => 'Reparações rápidas de canalização e eletricidade.',
        'Humidade Zero'                => 'Tratamento de humidades, infiltrações e impermeabilizações.',
        'Pinturas & Acabamentos Costa' => 'Pinturas interiores e exteriores e tratamento de paredes.',
        'Climatiza Porto'              => 'Instalação e manutenção de ar condicionado e bombas de calor.',
        'Carpintaria Moderna'          => 'Portas, roupeiros, rodapés e pequenos trabalhos em madeira.',
        'Jardins & Companhia'          => 'Manutenção de jardins, podas e sistemas de rega.',
        'Brilho Lar Limpezas'          => 'Limpezas domésticas e limpezas pós-obra.',
        'Assistência Total Lar'        => 'Canalização, eletricidade e eletrodomésticos num só contacto.',
        'Fix&Go Reparações'            => 'Reparações ao domicílio com marcação no próprio dia.',
        'Oficina do Lar'               => 'Pequenas obras e manutenção de apartamentos.',
        'Água & Luz Serviços'          => 'Especialistas em canalização e instalações elétricas.',
        'Engenho Casa'                 => 'Remodelações e reparações com garantia.',
        'Ponto Certo Manutenção'       => 'Manutenção preventiva de casas e condomínios.',
        'TecLar Assistência'           => 'Reparação de eletrodomésticos e instalações.',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(array_keys(self::CATALOG));

        return [
            'user_id'        => User::factory(),
            'company_name'   => $name,
            'nif'            => fake()->numerify('5########'),
            'phone'          => fake()->randomElement(['22', '91', '93', '96']) . fake()->numerify('#######'),
            'provider_email' => 'geral@' . Str::slug($name) . '.pt',
            'description'    => self::CATALOG[$name],
        ];
    }
}