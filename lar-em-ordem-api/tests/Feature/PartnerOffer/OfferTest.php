<?php

namespace Tests\Feature\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Ofertas (módulo 7): as ofertas do parceiro (todas, com filtro ?active=), criação,
 * detalhe (o dono vê sempre, os outros só as em vigor), edição/pausa, remoção e recomendações.
 * Usa os dados do OfferSeeder: o "Parceiro Lda" tem 3 ofertas em vigor, 1 expirada, 1 futura e 1 inativa.
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

    private function dadosOferta(array $extra = []): array
    {
        return $extra + [
            'title'       => 'Limpeza gratuita',
            'description' => 'Primeira limpeza sem custos.',
            'type'        => 'Limpeza',
            'url'         => 'https://parceiro.pt/limpeza',
            'start_date'  => now()->toDateString(),
            'end_date'    => now()->addMonth()->toDateString(),
        ];
    }

    // ------------------------------------------------------------------ index

    public function test_o_parceiro_ve_todas_as_suas_ofertas(): void
    {
        $resposta = $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers')
            ->assertOk()
            ->assertJsonCount(6, 'data');

        // da data de início mais recente para a mais antiga
        $datas = collect($resposta->json('data'))->pluck('start_date')->all();
        $this->assertSame(collect($datas)->sortDesc()->values()->all(), $datas);
    }

    public function test_o_parceiro_filtra_as_ofertas_por_ativas_ou_inativas(): void
    {
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers?active=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Oferta desativada');

        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/offers?active=true')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_sem_perfil_de_parceiro_nao_ha_lista_de_ofertas(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/offers')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------ store

    public function test_o_parceiro_cria_uma_oferta_ativa(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/offers', $this->dadosOferta())
            ->assertCreated()
            ->assertJsonPath('data.title', 'Limpeza gratuita')
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('offers', [
            'title'      => 'Limpeza gratuita',
            'partner_id' => $this->parceiro->partner->id,
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
            ->assertJsonValidationErrors(['title', 'description', 'type', 'start_date', 'url']);
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

        // pausada: sai das recomendações e deixa de ser visível para os outros
        $this->actingAs($this->morador)->getJson("/api/v1/offers/{$oferta->id}")->assertNotFound();
        $titulos = collect($this->actingAs($this->morador)->getJson('/api/v1/offers/recommendations')->json('data'))->pluck('title');
        $this->assertFalse($titulos->contains('Avaliação gratuita do imóvel'));

        $this->actingAs($this->parceiro)
            ->patchJson("/api/v1/offers/{$oferta->id}", ['active' => true])
            ->assertOk()
            ->assertJsonPath('data.active', true);

        $this->actingAs($this->morador)->getJson("/api/v1/offers/{$oferta->id}")->assertOk();
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
        $this->actingAs($this->parceiro)->getJson('/api/v1/offers')->assertJsonCount(5, 'data');
    }

    public function test_so_o_dono_apaga_a_oferta(): void
    {
        $this->actingAs($this->morador)
            ->deleteJson('/api/v1/offers/' . $this->oferta('Avaliação gratuita do imóvel')->id)
            ->assertForbidden();
    }

    // -------------------------------------------------------- recommendations

    public function test_as_recomendacoes_so_trazem_ofertas_em_vigor_com_o_parceiro(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson('/api/v1/offers/recommendations')
            ->assertOk()
            ->assertJsonCount(Offer::current()->count(), 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'start_date', 'partner' => ['id', 'company_name']]]]);

        $titulos = collect($resposta->json('data'))->pluck('title');
        $this->assertTrue($titulos->contains('Avaliação gratuita do imóvel'));
        $this->assertFalse($titulos->contains('Campanha de verão (terminada)'));
        $this->assertFalse($titulos->contains('Campanha de Natal (ainda não começou)'));
        $this->assertFalse($titulos->contains('Oferta desativada'));

        foreach ($resposta->json('data') as $oferta) {
            $this->assertTrue($oferta['active']);
        }
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
}