<?php

namespace Tests\Feature\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Parceiros (módulo 7): listagem, perfil próprio, detalhe com as ofertas em vigor,
 * criação, edição e remoção. Usa os dados do DatabaseSeeder/OfferSeeder:
 * "Parceiro Lda" (parceiro@example.com, NIF 123456789) com 3 ofertas em vigor,
 * 1 expirada, 1 futura e 1 inativa.
 */
class PartnerTest extends TestCase
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

    private function idParceiro(): string
    {
        return $this->parceiro->partner->id;
    }

    private function dadosParceiro(array $extra = []): array
    {
        return $extra + [
            'company_name' => 'Seguros Lda',
            'nif'          => '987654321',
            'phone'        => '912345678',
            'website'      => 'https://seguros.pt',
            'description'  => 'Seguros para a casa.',
        ];
    }

    // ------------------------------------------------------------ index / me

    public function test_index_lista_os_parceiros_sem_nif_nem_ofertas(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson('/api/v1/partners')
            ->assertOk()
            ->assertJsonCount(Partner::count(), 'data');

        foreach ($resposta->json('data') as $parceiro) {
            $this->assertArrayNotHasKey('nif', $parceiro);
            $this->assertArrayNotHasKey('offers', $parceiro);
            $this->assertArrayNotHasKey('active', $parceiro);
        }
    }

    public function test_no_index_o_parceiro_so_ve_o_seu_proprio_nif(): void
    {
        $lista = collect(
            $this->actingAs($this->parceiro)->getJson('/api/v1/partners')->assertOk()->json('data')
        );

        $this->assertSame('123456789', $lista->firstWhere('id', $this->idParceiro())['nif']);
        $this->assertArrayNotHasKey('nif', $lista->firstWhere('id', '!=', $this->idParceiro()));
    }

    public function test_me_devolve_o_perfil_do_parceiro_autenticado_com_nif(): void
    {
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/partners/me')
            ->assertOk()
            ->assertJsonPath('data.id', $this->idParceiro())
            ->assertJsonPath('data.company_name', 'Parceiro Lda')
            ->assertJsonPath('data.nif', '123456789');
    }

    public function test_me_sem_perfil_de_parceiro_da_403(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/partners/me')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------- show

    public function test_show_traz_so_as_ofertas_em_vigor_sem_nif_a_terceiros(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson('/api/v1/partners/' . $this->idParceiro())
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Parceiro Lda')
            ->assertJsonMissingPath('data.nif')
            ->assertJsonCount(3, 'data.offers');

        $titulos = collect($resposta->json('data.offers'))->pluck('title');
        $this->assertFalse($titulos->contains('Campanha de verão (terminada)'));
        $this->assertFalse($titulos->contains('Campanha de Natal (ainda não começou)'));
        $this->assertFalse($titulos->contains('Oferta desativada'));

        // da data de início mais recente para a mais antiga
        $datas = collect($resposta->json('data.offers'))->pluck('start_date')->all();
        $this->assertSame(collect($datas)->sortDesc()->values()->all(), $datas);
    }

    public function test_o_proprio_parceiro_ve_o_nif_no_show(): void
    {
        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/partners/' . $this->idParceiro())
            ->assertOk()
            ->assertJsonPath('data.nif', '123456789');
    }

    public function test_o_nif_tambem_fica_escondido_dentro_das_ofertas(): void
    {
        $oferta = Offer::current()->where('partner_id', $this->idParceiro())->firstOrFail();

        $this->actingAs($this->morador)
            ->getJson("/api/v1/offers/{$oferta->id}")
            ->assertOk()
            ->assertJsonPath('data.partner.id', $this->idParceiro())
            ->assertJsonMissingPath('data.partner.nif');

        foreach ($this->actingAs($this->morador)->getJson('/api/v1/offers/recommendations')->json('data') as $o) {
            $this->assertArrayNotHasKey('nif', $o['partner']);
        }
    }

    // ------------------------------------------------------------------ store

    public function test_cria_o_perfil_de_parceiro_e_atribui_a_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partners', $this->dadosParceiro())
            ->assertCreated()
            ->assertJsonPath('data.company_name', 'Seguros Lda')
            ->assertJsonPath('data.nif', '987654321');

        $this->assertTrue($user->fresh()->hasRole('partner'));
        $this->assertDatabaseHas('partners', ['user_id' => $user->id, 'nif' => '987654321']);
    }

    public function test_o_user_id_nao_vem_do_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partners', $this->dadosParceiro(['user_id' => $this->morador->id]))
            ->assertCreated();

        $this->assertNotNull($user->fresh()->partner);
        $this->assertNull($this->morador->fresh()->partner);
    }

    public function test_cada_utilizador_so_tem_um_perfil_de_parceiro(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/v1/partners', $this->dadosParceiro())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id' => 'Este utilizador já tem um perfil de parceiro.']);
    }

    public function test_store_valida_os_campos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/partners', ['nif' => '12', 'website' => 'não é url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'nif', 'phone', 'description', 'website']);

        $this->assertNull($user->fresh()->partner);
    }

    // ----------------------------------------------------------------- update

    public function test_o_parceiro_atualiza_so_os_campos_enviados(): void
    {
        $this->actingAs($this->parceiro)
            ->patchJson('/api/v1/partners/' . $this->idParceiro(), ['phone' => '911111111'])
            ->assertOk()
            ->assertJsonPath('data.phone', '911111111')
            ->assertJsonPath('data.company_name', 'Parceiro Lda')
            ->assertJsonPath('data.nif', '123456789');
    }

    public function test_o_active_enviado_no_body_e_ignorado(): void
    {
        $this->actingAs($this->parceiro)
            ->patchJson('/api/v1/partners/' . $this->idParceiro(), ['active' => false])
            ->assertOk()
            ->assertJsonMissingPath('data.active');

        // continua a aparecer na lista
        $lista = collect($this->actingAs($this->morador)->getJson('/api/v1/partners')->json('data'));
        $this->assertTrue($lista->contains('id', $this->idParceiro()));
    }

    public function test_so_o_dono_atualiza_o_perfil(): void
    {
        $this->actingAs($this->morador)
            ->patchJson('/api/v1/partners/' . $this->idParceiro(), ['phone' => '900000000'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- destroy

    public function test_o_dono_apaga_o_perfil_as_ofertas_e_perde_a_role(): void
    {
        $id = $this->idParceiro();

        $this->actingAs($this->parceiro)
            ->deleteJson("/api/v1/partners/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Parceiro removido.');

        $this->assertSoftDeleted('partners', ['id' => $id]);
        $this->assertSame(0, Offer::where('partner_id', $id)->count());
        $this->assertFalse($this->parceiro->fresh()->hasRole('partner'));

        // deixa de aparecer, e as ofertas saem das recomendações
        $this->actingAs($this->morador)->getJson("/api/v1/partners/{$id}")->assertNotFound();

        $recomendacoes = collect($this->actingAs($this->morador)->getJson('/api/v1/offers/recommendations')->json('data'));
        $this->assertFalse($recomendacoes->contains(fn ($o) => $o['partner']['id'] === $id));
    }

    public function test_so_o_dono_apaga_o_perfil(): void
    {
        $this->actingAs($this->morador)
            ->deleteJson('/api/v1/partners/' . $this->idParceiro())
            ->assertForbidden();
    }

    // ------------------------------------------------------- rotas removidas

    public function test_as_rotas_antigas_ja_nao_existem(): void
    {
        $id = $this->idParceiro();

        $this->actingAs($this->morador)->getJson("/api/v1/partners/offers/{$id}")->assertNotFound();
        $this->actingAs($this->morador)->getJson("/api/v1/offers/partner/{$id}")->assertNotFound();
    }
}