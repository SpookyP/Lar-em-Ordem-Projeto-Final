<?php

namespace Database\Factories\Offer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Ofertas realistas para a casa (título, descrição e tipo coerentes).
     * {pct}, {meses} e {preco} são trocados por números ao gerar, para as ofertas não ficarem todas iguais.
     */
    private const CATALOG = [
        ['type' => 'Seguro',           'title' => 'Seguro multirriscos com {meses} meses grátis',        'description' => 'Proteja a sua casa contra incêndio, inundação e roubo. Ao subscrever online, os primeiros {meses} meses ficam por nossa conta.'],
        ['type' => 'Seguro',           'title' => '{pct}% de desconto no seguro de recheio',             'description' => 'Cobertura para móveis, eletrodomésticos e equipamentos. Desconto válido na primeira anuidade.'],
        ['type' => 'Energia',          'title' => '{pct}% de desconto na fatura da luz',                 'description' => 'Mude para o nosso tarifário de eletricidade 100% renovável e poupe durante o primeiro ano. Sem fidelização.'],
        ['type' => 'Energia',          'title' => 'Auditoria energética gratuita',                        'description' => 'Um técnico visita a sua casa, avalia o isolamento e os consumos e indica as melhorias com maior poupança.'],
        ['type' => 'Energia',          'title' => 'Painéis solares com {pct}% de desconto na instalação', 'description' => 'Produza a sua própria energia. Inclui estudo de viabilidade e apoio na candidatura a incentivos.'],
        ['type' => 'Limpeza',          'title' => 'Primeira limpeza com {pct}% de desconto',             'description' => 'Limpeza completa da casa por profissionais certificados, com produtos ecológicos incluídos.'],
        ['type' => 'Limpeza',          'title' => 'Limpeza pós-obra a preço especial',                    'description' => 'Remoção de pó, restos de tinta e entulho fino depois de obras ou remodelações.'],
        ['type' => 'Mudanças',         'title' => 'Mudança com embalagem incluída',                       'description' => 'Tratamos da embalagem, do transporte e da montagem dos móveis. Orçamento gratuito em 24 horas.'],
        ['type' => 'Mudanças',         'title' => '{pct}% de desconto em mudanças dentro do concelho',    'description' => 'Equipa de dois profissionais e carrinha até 4 horas, com seguro de transporte incluído.'],
        ['type' => 'Desconto',         'title' => '{pct}% de desconto em eletrodomésticos',              'description' => 'Desconto em frigoríficos, máquinas de lavar e fornos de classe energética A, com entrega e instalação.'],
        ['type' => 'Desconto',         'title' => '{pct}% de desconto em material de bricolage',         'description' => 'Ferramentas, tintas e acessórios para pequenas reparações em casa, na loja ou online.'],
        ['type' => 'Telecomunicações', 'title' => 'Internet fibra com {meses} meses grátis',              'description' => 'Fibra até 1 Gbps com router Wi-Fi 6 incluído. Instalação gratuita.'],
        ['type' => 'Manutenção',       'title' => 'Revisão da caldeira por {preco} €',                    'description' => 'Revisão anual do esquentador ou da caldeira, com certificado de inspeção.'],
        ['type' => 'Manutenção',       'title' => 'Check-up gratuito da instalação elétrica',             'description' => 'Verificação do quadro elétrico, das tomadas e da ligação à terra por eletricista certificado.'],
        ['type' => 'Segurança',        'title' => 'Alarme com instalação gratuita',                       'description' => 'Sistema de alarme ligado a uma central de monitorização 24 horas, com aplicação para o telemóvel.'],
        ['type' => 'Decoração',        'title' => '{pct}% de desconto em cortinados e estores',           'description' => 'Medição ao domicílio e confeção à medida para todas as divisões da casa.'],
    ];

    /**
     * Define the model's default state (oferta em vigor, de um tipo ao calhas).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
            'start_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'end_date'   => now()->addDays(fake()->numberBetween(15, 90))->toDateString(),
            'active'     => true,
        ] + $this->fromCatalog(self::CATALOG);
    }

    /**
     * Oferta de um tipo (ex.: 'Seguro'), para combinar com a área do parceiro.
     * Se o tipo não tiver ofertas no catálogo (ou for null), escolhe de todas.
     */
    public function ofType(?string $type): static
    {
        $offers = array_values(array_filter(self::CATALOG, fn (array $offer) => $offer['type'] === $type));

        return $this->state(fn () => $this->fromCatalog($offers ?: self::CATALOG));
    }

    /**
     * Escolhe uma oferta da lista e troca {pct}, {meses} e {preco} por números.
     *
     * @return array{title: string, description: string, type: string, url: string|null}
     */
    private function fromCatalog(array $catalog): array
    {
        $offer = fake()->randomElement($catalog);

        // números que variam de oferta para oferta
        $values = [
            '{pct}'   => fake()->randomElement([10, 15, 20, 25, 30]),
            '{meses}' => fake()->numberBetween(2, 3),
            '{preco}' => fake()->randomElement([39, 49, 59]),
        ];

        $title = strtr($offer['title'], $values);

        return [
            'title'       => $title,
            'description' => strtr($offer['description'], $values),
            'type'        => $offer['type'],
            'url'         => fake()->optional()->passthrough('https://www.exemplo.pt/ofertas/' . Str::slug($title)),
        ];
    }

    /**
     * Oferta que já terminou.
     */
    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date'   => now()->subMonth()->toDateString(),
        ]);
    }

    /**
     * Oferta que ainda não começou.
     */
    public function upcoming(): static
    {
        return $this->state([
            'start_date' => now()->addWeeks(2)->toDateString(),
            'end_date'   => now()->addMonths(2)->toDateString(),
        ]);
    }

    /**
     * Oferta desativada pelo parceiro.
     */
    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}