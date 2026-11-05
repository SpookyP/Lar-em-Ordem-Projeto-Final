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
        'Casa Segura Seguros'      => ['area' => 'Seguro',           'description' => 'Seguros multirriscos e de recheio pensados para quem arrenda ou tem casa própria.'],
        'Abrigo Seguros'           => ['area' => 'Seguro',           'description' => 'Mediadora de seguros para a habitação, com apoio em caso de sinistro 24 horas por dia.'],
        'Sol Nascente Energia'     => ['area' => 'Energia',          'description' => 'Instalação de painéis solares e baterias para casas e condomínios.'],
        'VerdeLuz Eletricidade'    => ['area' => 'Energia',          'description' => 'Comercializadora de eletricidade 100% renovável, sem fidelização.'],
        'EcoLar Eficiência'        => ['area' => 'Energia',          'description' => 'Auditorias energéticas e soluções de isolamento para reduzir a fatura.'],
        'Brilho Total Limpezas'    => ['area' => 'Limpeza',          'description' => 'Limpezas domésticas regulares e limpezas profundas, com produtos ecológicos.'],
        'Casa Limpa Serviços'      => ['area' => 'Limpeza',          'description' => 'Limpezas pós-obra, de vidros e de áreas comuns de condomínios.'],
        'Muda Já Transportes'      => ['area' => 'Mudanças',         'description' => 'Mudanças residenciais com embalagem, transporte e montagem de móveis.'],
        'Rota Certa Mudanças'      => ['area' => 'Mudanças',         'description' => 'Pequenas mudanças e transporte de móveis dentro do concelho.'],
        'FibraNet Comunicações'    => ['area' => 'Telecomunicações', 'description' => 'Internet por fibra, televisão e telefone fixo para a casa.'],
        'Técnico em Casa'          => ['area' => 'Manutenção',       'description' => 'Revisões de caldeiras, esquentadores e instalações elétricas por técnicos certificados.'],
        'Reparar Já Manutenção'    => ['area' => 'Manutenção',       'description' => 'Pequenas reparações domésticas: canalização, eletricidade e carpintaria.'],
        'Guarda Lar Alarmes'       => ['area' => 'Segurança',        'description' => 'Sistemas de alarme e videovigilância com monitorização 24 horas.'],
        'Atelier Cortinas & Cia'   => ['area' => 'Decoração',        'description' => 'Cortinados, estores e têxteis para a casa, feitos à medida.'],
        'Bricolar Bricolage'       => ['area' => 'Desconto',         'description' => 'Loja de bricolage com ferramentas, tintas e material para obras em casa.'],
        'Eletro Lar'               => ['area' => 'Desconto',         'description' => 'Eletrodomésticos de baixo consumo com entrega e instalação ao domicílio.'],
        'Lar Protegido Seguros'    => ['area' => 'Seguro',           'description' => 'Seguros de habitação e responsabilidade civil com simulação online em minutos.'],
        'Zelo Seguros'             => ['area' => 'Seguro',           'description' => 'Proteção para casa, recheio e assistência ao lar, com franquias reduzidas.'],
        'Raio Verde Energia'       => ['area' => 'Energia',          'description' => 'Eletricidade e gás natural em pacote único, com descontos por fatura eletrónica.'],
        'Poupa Watt Energia'       => ['area' => 'Energia',          'description' => 'Comercializadora com tarifas bi-horárias e acompanhamento de consumos.'],
        'Aquece Lar Climatização'  => ['area' => 'Energia',          'description' => 'Instalação e manutenção de ar condicionado e bombas de calor.'],
        'Fresca Casa Limpezas'     => ['area' => 'Limpeza',          'description' => 'Limpezas regulares ao domicílio com equipas fixas e produtos biodegradáveis.'],
        'Cristal Limpezas'         => ['area' => 'Limpeza',          'description' => 'Limpeza de vidros, estofos e tapetes, com recolha e entrega.'],
        'Leve e Rápido Mudanças'   => ['area' => 'Mudanças',         'description' => 'Mudanças expresso para apartamentos, com seguro de bens incluído.'],
        'Caixa a Caixa Mudanças'   => ['area' => 'Mudanças',         'description' => 'Embalagem, transporte e armazenamento temporário de mobiliário.'],
        'ConectaLar Comunicações'  => ['area' => 'Telecomunicações', 'description' => 'Pacotes de fibra, TV e telemóvel com instalação em 48 horas.'],
        'NetCasa Fibra'            => ['area' => 'Telecomunicações', 'description' => 'Internet por fibra até 1 Gbps, sem fidelização e com router grátis.'],
        'Smart Home Automação'     => ['area' => 'Telecomunicações', 'description' => 'Automação de luzes, estores e climatização controlada pelo telemóvel.'],
        'Mãos à Obra Manutenção'   => ['area' => 'Manutenção',       'description' => 'Pequenos arranjos domésticos, montagem de móveis e pinturas.'],
        'Chave Mestra Reparações'  => ['area' => 'Manutenção',       'description' => 'Serviço de urgências para canalização, eletricidade e fechaduras.'],
        'Canalpro Canalizações'    => ['area' => 'Manutenção',       'description' => 'Deteção de fugas, desentupimentos e substituição de canalizações.'],
        'Jardim Perfeito'          => ['area' => 'Manutenção',       'description' => 'Manutenção de jardins, poda e sistemas de rega automática.'],
        'Vigia Segura Alarmes'     => ['area' => 'Segurança',        'description' => 'Alarmes com central de monitorização e botão de pânico.'],
        'OlhoVivo Videovigilância' => ['area' => 'Segurança',        'description' => 'Câmaras com gravação na cloud e acesso remoto em tempo real.'],
        'Porta Segura Fechaduras'  => ['area' => 'Segurança',        'description' => 'Portas blindadas e fechaduras inteligentes com instalação.'],
        'Ambiente & Estilo'        => ['area' => 'Decoração',        'description' => 'Projetos de decoração de interiores e mobiliário por medida.'],
        'Tecido Lar Têxteis'       => ['area' => 'Decoração',        'description' => 'Roupa de cama, atoalhados e cortinados para toda a casa.'],
        'Eletro Mais'              => ['area' => 'Desconto',         'description' => 'Grandes e pequenos eletrodomésticos com descontos para residentes.'],
        'Mobília Nova'             => ['area' => 'Desconto',         'description' => 'Mobiliário de sala, quarto e cozinha com entrega e montagem.'],
        'Ferragens do Lar'         => ['area' => 'Desconto',         'description' => 'Ferramentas, parafusaria e material de construção a preços de armazém.'],
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