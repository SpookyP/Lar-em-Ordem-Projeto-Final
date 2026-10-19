<?php

namespace Database\Seeders;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    /**
     * Cria ofertas para o parceiro de demonstração (em vigor, expirada, futura e inativa,
     * para testar os filtros) e ofertas em vigor para os restantes parceiros.
     * Precisa do PartnerSeeder.
     */
    public function run(): void
    {
        $parceiro = User::where('email', 'parceiro@example.com')->firstOrFail()->partner;

        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => '10% de desconto na gestão do arrendamento',
            'description' => 'Desconto no primeiro ano para residentes do Lar em Ordem.',
            'type'        => 'Desconto',
        ]);
        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => 'Avaliação gratuita do imóvel',
            'description' => 'Avaliação de mercado sem custos nem compromisso.',
            'type'        => 'Serviço',
            'end_date'    => null,
        ]);
        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => 'Seguro multirriscos com 2 meses grátis',
            'description' => 'Para novas apólices subscritas durante a campanha.',
            'type'        => 'Seguro',
        ]);
        Offer::factory()->expired()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Campanha de verão (terminada)',
            'type'       => 'Desconto',
        ]);
        Offer::factory()->upcoming()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Campanha de Natal (ainda não começou)',
            'type'       => 'Desconto',
        ]);
        Offer::factory()->inactive()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Oferta desativada',
            'type'       => 'Serviço',
        ]);

        // Restantes parceiros: 1 a 3 ofertas em vigor cada
        Partner::whereKeyNot($parceiro->id)->get()->each(
            fn (Partner $partner) => Offer::factory(fake()->numberBetween(1, 3))->create(['partner_id' => $partner->id])
        );
    }
}