<?php

namespace Tests\Feature\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\Offer\OfferCategory;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Offers (module 7): the partner's offers (paginated, ?status= filter), creating with a type,
 * detail (the owner always sees it, others only current ones), edit/pause, removal,
 * recommendations (current, paginated, ?search= and ?category_id=) and the type catalog.
 * Uses the OfferSeeder data: "Parceiro Lda" has 3 current offers, 1 expired, 1 upcoming and 1 inactive.
 */
class OfferTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $morador;
    private User $parceiro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->morador  = User::where('email', 'morador@example.com')->firstOrFail();
        $this->parceiro = User::where('email', 'parceiro@example.com')->firstOrFail();
    }

    // ---------------------------------------------------------------- helpers

    private function oferta(string $titulo): Offer
    {
        return Offer::where('title', $titulo)->firstOrFail();
    }

    private function tipo(string $nome): int
    {
        return OfferCategory::where('name', $nome)->value('id');
    }

    private function dadosOferta(array $extra = []): array
    {
        return $extra + [
            'title'             => 'Limpeza gratuita',
            'description'       => 'Primeira limpeza sem custos.',
            'offer_category_id' => $this->tipo('Limpeza'),
            'url'               => 'https://parceiro.pt/limpeza',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonth()->toDateString(),
        ];
    }

    // ------------------------------------------------------------------ index

    public function test_o_parceiro_ve_as_suas_ofertas_paginadas(): void
    {
        // 5 per page by default
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('meta.last_page', 2);

        $resposta = $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers?per_page=15')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'category' => ['id', 'name'], 'active']]]);

        // newest start date first
        $datas = collect($resposta->json('data'))->pluck('start_date')->all();
        $this->assertSame(collect($datas)->sortDesc()->values()->all(), $datas);
    }

    public function test_o_parceiro_filtra_as_ofertas_por_estado(): void
    {
        $esperado = [
            'current'   => ['10% de desconto na gestão do arrendamento', 'Avaliação gratuita do imóvel', 'Seguro multirriscos com 2 meses grátis'],
            'scheduled' => ['Campanha de Natal (ainda não começou)'],
            'expired'   => ['Campanha de verão (terminada)'],
            'inactive'  => ['Oferta desativada'],
        ];

        foreach ($esperado as $status => $titulos) {
            $obtidos = collect($this->actingAs($this->parceiro)
                ->getJson("/api/v1/offers?status={$status}&per_page=15")
                ->assertOk()
                ->json('data'))->pluck('title')->sort()->values()->all();

            $this->assertSame($titulos, $obtidos, "estado {$status}");
        }
    }

    public function test_cada_oferta_esta_num_so_estado(): void
    {
        // a paused offer stays "inactive" even if it is within its dates, expired or scheduled
        $pausadas = [
            Offer::factory()->inactive()->create(['partner_id' => $this->parceiro->partner->id]),
            Offer::factory()->inactive()->expired()->create(['partner_id' => $this->parceiro->partner->id]),
            Offer::factory()->inactive()->upcoming()->create(['partner_id' => $this->parceiro->partner->id]),
        ];

        $total = 0;
        foreach (['current', 'scheduled', 'expired', 'inactive'] as $status) {
            $total += $this->actingAs($this->parceiro)->getJson("/api/v1/offers?status={$status}")->json('meta.total');
        }

        $this->assertSame($this->parceiro->partner->offers()->count(), $total);
        $this->actingAs($this->parceiro)->getJson('/api/v1/offers?status=inactive')->assertJsonPath('meta.total', 1 + count($pausadas));
    }

    public function test_o_estado_tem_de_ser_valido(): void
    {
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers?status=ativa')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Estado inválido.']);
    }

    public function test_sem_perfil_de_parceiro_nao_ha_lista_de_ofertas(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------ store

    public function test_o_parceiro_cria_uma_oferta_ativa_com_tipo(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', $this->dadosOferta())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Limpeza gratuita')
            ->assertJsonPath('data.category.name', 'Limpeza')
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('offers', [
            'title'             => 'Limpeza gratuita',
            'partner_id'        => $this->parceiro->partner->id,
            'offer_category_id' => $this->tipo('Limpeza'),
        ]);
    }

    public function test_o_partner_id_nao_vem_do_body(): void
    {
        $outro = Partner::whereKeyNot($this->parceiro->partner->id)->firstOrFail();

        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', $this->dadosOferta(['partner_id' => $outro->id]))
            ->assertCreated();

        $this->assertDatabaseHas('offers', [
            'title'      => 'Limpeza gratuita',
            'partner_id' => $this->parceiro->partner->id,
        ]);
    }

    public function test_a_data_de_fim_nao_pode_ser_anterior_a_de_inicio(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', $this->dadosOferta([
                'start_date' => now()->toDateString(),
                'end_date'   => now()->subDay()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date' => 'A data de fim não pode ser anterior à data de início.']);
    }

    public function test_store_valida_os_campos(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', ['url' => 'não é url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'offer_category_id' => 'Escolha o tipo de oferta.', 'start_date', 'url']);

        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', $this->dadosOferta(['offer_category_id' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_category_id' => 'O tipo de oferta indicado não existe.']);
    }

    public function test_so_parceiros_criam_ofertas(): void
    {
        $this->actingAs($this->morador)
            ->postJson('/api/v1/offers', $this->dadosOferta())
            ->assertForbidden();
    }

    // ------------------------------------------------------------------- show

    public function test_qualquer_utilizador_ve_uma_oferta_em_vigor_com_o_parceiro(): void
    {
        $oferta = $this->oferta('Avaliação gratuita do imóvel');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/offers/{$oferta->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Avaliação gratuita do imóvel')
            ->assertJsonPath('data.category.name', 'Outros')
            ->assertJsonPath('data.partner.company_name', 'Parceiro Lda');
    }

    public function test_ofertas_fora_de_vigor_dao_404_a_quem_nao_e_dono(): void
    {
        foreach (['Campanha de verão (terminada)', 'Campanha de Natal (ainda não começou)', 'Oferta desativada'] as $titulo) {
            $this->actingAs($this->morador)
                ->getJson('/api/v1/offers/' . $this->oferta($titulo)->id)
                ->assertNotFound();
        }
    }

    public function test_o_dono_ve_sempre_as_suas_ofertas(): void
    {
        foreach (['Campanha de verão (terminada)', 'Campanha de Natal (ainda não começou)', 'Oferta desativada'] as $titulo) {
            $this->actingAs($this->parceiro)
                ->getJson('/api/v1/offers/' . $this->oferta($titulo)->id)
                ->assertOk()
                ->assertJsonPath('data.title', $titulo);
        }
    }

    // ----------------------------------------------------------------- update

    public function test_o_parceiro_pausa_e_reativa_uma_oferta(): void
    {
        $oferta = $this->oferta('Avaliação gratuita do imóvel');

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['active' => false])
            ->assertOk()
            ->assertJsonPath('data.active', false);

        // paused: leaves the recommendations and is no longer visible to others
        $this->actingAs($this->morador)->getJson("/api/v1/offers/{$oferta->id}")->assertNotFound();
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations?search=Avaliação gratuita do imóvel')
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['active' => true])
            ->assertOk()
            ->assertJsonPath('data.active', true);

        $this->actingAs($this->morador)->getJson("/api/v1/offers/{$oferta->id}")->assertOk();
    }

    public function test_o_parceiro_muda_o_tipo_da_oferta(): void
    {
        $oferta = $this->oferta('Avaliação gratuita do imóvel');

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['offer_category_id' => $this->tipo('Seguros')])
            ->assertOk()
            ->assertJsonPath('data.category.name', 'Seguros');

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['offer_category_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['offer_category_id']);
    }

    public function test_a_data_de_fim_e_comparada_com_a_data_de_inicio_guardada(): void
    {
        $oferta = $this->oferta('Avaliação gratuita do imóvel');

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", [
                'end_date' => $oferta->start_date->copy()->subDay()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date' => 'A data de fim não pode ser anterior à data de início.']);
    }

    public function test_so_o_dono_atualiza_a_oferta(): void
    {
        $this->actingAs($this->morador)
            ->patchJson('/api/v1/offers/' . $this->oferta('Avaliação gratuita do imóvel')->id, ['title' => 'x'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- destroy

    public function test_o_dono_apaga_uma_oferta(): void
    {
        $oferta = $this->oferta('Avaliação gratuita do imóvel');

        $this->actingAs($this->parceiro)
            ->deleteJson("/api/v1/offers/{$oferta->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Oferta removida.');

        $this->assertSoftDeleted('offers', ['id' => $oferta->id]);
        $this->actingAs($this->parceiro)->getJson('/api/v1/offers')->assertJsonPath('meta.total', 5);
    }

    public function test_so_o_dono_apaga_a_oferta(): void
    {
        $this->actingAs($this->morador)
            ->deleteJson('/api/v1/offers/' . $this->oferta('Avaliação gratuita do imóvel')->id)
            ->assertForbidden();
    }

    // -------------------------------------------------------- recommendations

    public function test_as_recomendacoes_sao_as_ofertas_em_vigor_paginadas_com_os_tipos(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations')
            ->assertOk()
            ->assertJsonPath('meta.total', Offer::current()->count())
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonCount(10, 'categories')
            ->assertJsonStructure(['data' => [['id', 'title', 'start_date', 'category' => ['id', 'name'], 'partner' => ['id', 'company_name']]]]);

        foreach ($resposta->json('data') as $oferta) {
            $this->assertTrue($oferta['active']);
        }

        // "Parceiro Lda" offers that aren't current never show
        foreach (['Campanha de verão', 'Campanha de Natal', 'Oferta desativada'] as $titulo) {
            $this->actingAs($this->morador)
                ->getJson("/api/v1/offers/recommendations?search={$titulo}")
                ->assertJsonPath('meta.total', 0);
        }
    }

    public function test_as_recomendacoes_pesquisam_no_titulo_na_descricao_e_no_parceiro(): void
    {
        // title
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations?search=avaliação gratuita do imóvel')
            ->assertJsonPath('meta.total', 1);

        // description ("Avaliação de mercado sem custos nem compromisso.")
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations?search=sem custos nem compromisso')
            ->assertJsonPath('meta.total', 1);

        // partner name: the 3 current offers of "Parceiro Lda"
        $ofertas = $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations?search=Parceiro Lda&per_page=15')
            ->assertJsonPath('meta.total', 3)
            ->json('data');
        $this->assertSame(['Parceiro Lda'], array_values(array_unique(array_column(array_column($ofertas, 'partner'), 'company_name'))));
    }

    public function test_as_recomendacoes_filtram_por_tipo(): void
    {
        $seguros = $this->tipo('Seguros');

        $ofertas = $this->actingAs($this->morador)
            ->getJson("/api/v1/offers/recommendations?category_id={$seguros}&per_page=15")
            ->assertOk()
            ->assertJsonPath('meta.total', Offer::current()->where('offer_category_id', $seguros)->count())
            ->json('data');

        $this->assertNotEmpty($ofertas);
        $this->assertSame(['Seguros'], array_values(array_unique(array_column(array_column($ofertas, 'category'), 'name'))));

        // search and type together
        $this->actingAs($this->morador)
            ->getJson("/api/v1/offers/recommendations?category_id={$seguros}&search=Parceiro Lda")
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Seguro multirriscos com 2 meses grátis');
    }

    public function test_as_recomendacoes_validam_os_filtros(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations?category_id=999&search=' . str_repeat('a', 101))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id' => 'O tipo de oferta indicado não existe.',
                'search'      => 'A pesquisa não pode ter mais de 100 caracteres.',
            ]);
    }

    public function test_ofertas_que_comecam_ou_acabam_hoje_estao_em_vigor(): void
    {
        $hoje = now()->toDateString();

        $comecaHoje   = Offer::factory()->create(['start_date' => $hoje, 'end_date' => null]);
        $acabaHoje    = Offer::factory()->create(['start_date' => now()->subWeek()->toDateString(), 'end_date' => $hoje]);
        $acabouOntem  = Offer::factory()->create(['start_date' => now()->subWeek()->toDateString(), 'end_date' => now()->subDay()->toDateString()]);
        $comecaAmanha = Offer::factory()->create(['start_date' => now()->addDay()->toDateString(), 'end_date' => null]);

        $emVigor = Offer::current()->pluck('id');

        $this->assertTrue($emVigor->contains($comecaHoje->id));
        $this->assertTrue($emVigor->contains($acabaHoje->id));
        $this->assertFalse($emVigor->contains($acabouOntem->id));
        $this->assertFalse($emVigor->contains($comecaAmanha->id));
    }

    // ------------------------------------------------------------------- types

    public function test_o_catalogo_de_tipos_esta_disponivel_para_o_formulario(): void
    {
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offer-categories')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.name', 'Seguros')
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }

    public function test_editar_so_a_data_de_inicio_nao_pode_passar_a_data_de_fim(): void
    {
        $oferta = Offer::where('partner_id', $this->parceiro->partner->id)->whereNotNull('end_date')->firstOrFail();
        $depoisDoFim = $oferta->end_date->copy()->addDay()->toDateString();

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['start_date' => $depoisDoFim])
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_date');

        // sending the new end date too, it is accepted
        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['start_date' => $depoisDoFim, 'end_date' => null])
            ->assertOk();
    }
}