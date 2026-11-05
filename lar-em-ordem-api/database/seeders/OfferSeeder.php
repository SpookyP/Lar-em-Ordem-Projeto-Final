<?php

namespace Database\Seeders;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\Offer\OfferCategory;
use App\Models\User\User;
use Database\Factories\User\PartnerFactory;
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
        $tipo = fn (string $nome) => OfferCategory::where('name', $nome)->value('id');

        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => '10% de desconto na gestão do arrendamento',
            'description' => 'Desconto no primeiro ano para residentes do Lar em Ordem.',
            'offer_category_id'        => $tipo('Outros'),
        ]);
        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => 'Avaliação gratuita do imóvel',
            'description' => 'Avaliação de mercado sem custos nem compromisso.',
            'offer_category_id'        => $tipo('Outros'),
            'end_date'    => null,
        ]);
        Offer::factory()->create([
            'partner_id'  => $parceiro->id,
            'title'       => 'Seguro multirriscos com 2 meses grátis',
            'description' => 'Para novas apólices subscritas durante a campanha.',
            'offer_category_id'        => $tipo('Seguros'),
        ]);
        Offer::factory()->expired()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Campanha de verão (terminada)',
            'offer_category_id'       => $tipo('Outros'),
        ]);
        Offer::factory()->upcoming()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Campanha de Natal (ainda não começou)',
            'offer_category_id'       => $tipo('Outros'),
        ]);
        Offer::factory()->inactive()->create([
            'partner_id' => $parceiro->id,
            'title'      => 'Oferta desativada',
            'offer_category_id'       => $tipo('Outros'),
        ]);

        // Restantes parceiros: 1 a 3 ofertas em vigor cada, da área da empresa
        Partner::whereKeyNot($parceiro->id)->get()->each(
            fn (Partner $partner) => Offer::factory(fake()->numberBetween(1, 6))
                ->ofType(PartnerFactory::areaOf($partner->company_name))
                ->create(['partner_id' => $partner->id])
        );
    }
}