<?php

namespace Database\Factories\User;

use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Empresas parceiras fictícias: nome → área e descrição.
     * A área não é guardada no parceiro: serve para o OfferSeeder criar ofertas do tipo certo
     * (OfferFactory::ofType). As áreas são os tipos do catálogo da OfferFactory.
     */
    public const CATALOG = [
        'Casa Segura Seguros'    => ['area' => 'Seguro',           'description' => 'Seguros multirriscos e de recheio pensados para quem arrenda ou tem casa própria.'],
        'Abrigo Seguros'         => ['area' => 'Seguro',           'description' => 'Mediadora de seguros para a habitação, com apoio em caso de sinistro 24 horas por dia.'],
        'Sol Nascente Energia'   => ['area' => 'Energia',          'description' => 'Instalação de painéis solares e baterias para casas e condomínios.'],
        'VerdeLuz Eletricidade'  => ['area' => 'Energia',          'description' => 'Comercializadora de eletricidade 100% renovável, sem fidelização.'],
        'EcoLar Eficiência'      => ['area' => 'Energia',          'description' => 'Auditorias energéticas e soluções de isolamento para reduzir a fatura.'],
        'Brilho Total Limpezas'  => ['area' => 'Limpeza',          'description' => 'Limpezas domésticas regulares e limpezas profundas, com produtos ecológicos.'],
        'Casa Limpa Serviços'    => ['area' => 'Limpeza',          'description' => 'Limpezas pós-obra, de vidros e de áreas comuns de condomínios.'],
        'Muda Já Transportes'    => ['area' => 'Mudanças',         'description' => 'Mudanças residenciais com embalagem, transporte e montagem de móveis.'],
        'Rota Certa Mudanças'    => ['area' => 'Mudanças',         'description' => 'Pequenas mudanças e transporte de móveis dentro do concelho.'],
        'FibraNet Comunicações'  => ['area' => 'Telecomunicações', 'description' => 'Internet por fibra, televisão e telefone fixo para a casa.'],
        'Técnico em Casa'        => ['area' => 'Manutenção',       'description' => 'Revisões de caldeiras, esquentadores e instalações elétricas por técnicos certificados.'],
        'Reparar Já Manutenção'  => ['area' => 'Manutenção',       'description' => 'Pequenas reparações domésticas: canalização, eletricidade e carpintaria.'],
        'Guarda Lar Alarmes'     => ['area' => 'Segurança',        'description' => 'Sistemas de alarme e videovigilância com monitorização 24 horas.'],
        'Atelier Cortinas & Cia' => ['area' => 'Decoração',        'description' => 'Cortinados, estores e têxteis para a casa, feitos à medida.'],
        'Bricolar Bricolage'     => ['area' => 'Desconto',         'description' => 'Loja de bricolage com ferramentas, tintas e material para obras em casa.'],
        'Eletro Lar'             => ['area' => 'Desconto',         'description' => 'Eletrodomésticos de baixo consumo com entrega e instalação ao domicílio.'],
    ];

    public function definition(): array
    {
        // unique(): no seeder (10 parceiros) não se repetem empresas
        $name = fake()->unique()->randomElement(array_keys(self::CATALOG));

        return [
            'user_id'      => User::factory(),
            'company_name' => $name,
            'nif'          => fake()->numerify('5########'),
            'phone'        => fake()->numerify('2########'),
            'website'      => 'https://www.' . Str::slug($name, '') . '.pt',
            'description'  => self::CATALOG[$name]['description'],
        ];
    }

    /**
     * Área da empresa (para criar ofertas do tipo certo), ou null se o nome não for do catálogo.
     */
    public static function areaOf(string $companyName): ?string
    {
        return self::CATALOG[$companyName]['area'] ?? null;
    }
}