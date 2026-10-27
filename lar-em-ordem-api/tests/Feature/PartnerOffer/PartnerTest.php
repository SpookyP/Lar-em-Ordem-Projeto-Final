<?php

namespace Tests\Feature\PartnerOffer;

use App\Models\Offer\Offer;
use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Parceiros (módulo 7): listagem, perfil próprio e detalhe com as ofertas em vigor.
 * A criação, a edição e a remoção do perfil passam pelos endpoints dos perfis
 * (POST/DELETE /api/user/profile e PUT /user/profile-information), que usam o PartnerService.
 * Usa os dados do DatabaseSeeder/OfferSeeder: "Parceiro Lda" (parceiro@example.com, NIF 123456789)
 * com 3 ofertas em vigor, 1 expirada, 1 futura e 1 inativa.
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
            'role'         => 'partner',
            'company_name' => 'Seguros Lda',
            'nif'          => '987654321',
            'phone'        => '912345678',
            'website'      => 'https://seguros.pt',
            'description'  => 'Seguros para a casa.',
        ];
    }

    /** Edita o perfil do "Parceiro Lda" (o Fortify responde 200 sem corpo). */
    private function editar(array $dados)
    {
        return $this->actingAs($this->parceiro)
            ->putJson('/user/profile-information', ['role' => 'partner'] + $dados);
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

    // ------------------------------------------ criação (POST /api/user/profile)

    public function test_cria_o_perfil_de_parceiro_e_atribui_a_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/user/profile', $this->dadosParceiro())
            ->assertCreated();

        $this->assertTrue($user->fresh()->hasRole('partner'));
        $this->assertDatabaseHas('partners', ['user_id' => $user->id, 'company_name' => 'Seguros Lda', 'nif' => '987654321']);
    }

    public function test_o_user_id_nao_vem_do_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/user/profile', $this->dadosParceiro(['user_id' => $this->morador->id]))
            ->assertCreated();

        $this->assertNotNull($user->fresh()->partner);
        $this->assertNull($this->morador->fresh()->partner);
    }

    public function test_cada_utilizador_so_tem_um_perfil_de_parceiro(): void
    {
        $this->actingAs($this->parceiro)
            ->postJson('/api/user/profile', $this->dadosParceiro())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role' => 'Esta conta já tem um perfil de partner associado.']);
    }

    public function test_a_criacao_valida_os_campos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/user/profile', ['role' => 'partner', 'nif' => '12', 'website' => 'não é url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'nif', 'phone', 'description', 'website']);

        $this->assertNull($user->fresh()->partner);
    }

    // ---------------------------------- edição (PUT /user/profile-information)

    public function test_o_parceiro_atualiza_so_os_campos_enviados(): void
    {
        $this->editar(['phone' => '911111111'])->assertOk();

        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/partners/me')
            ->assertJsonPath('data.phone', '911111111')
            ->assertJsonPath('data.company_name', 'Parceiro Lda')
            ->assertJsonPath('data.nif', '123456789');
    }

    public function test_muda_todos_os_campos_do_perfil(): void
    {
        $this->editar([
            'company_name' => 'Parceiro Renovado Lda',
            'nif'          => '987654321',
            'phone'        => '933333333',
            'website'      => 'https://renovado.pt',
            'description'  => 'Nova descrição.',
        ])->assertOk();

        $this->actingAs($this->parceiro)
            ->getJson('/api/v1/partners/me')
            ->assertJsonPath('data.company_name', 'Parceiro Renovado Lda')
            ->assertJsonPath('data.nif', '987654321')
            ->assertJsonPath('data.phone', '933333333')
            ->assertJsonPath('data.website', 'https://renovado.pt')
            ->assertJsonPath('data.description', 'Nova descrição.');
    }

    public function test_a_edicao_valida_os_campos(): void
    {
        $this->editar(['nif' => '12', 'website' => 'não é url'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nif', 'website']);

        $this->assertSame('123456789', $this->parceiro->partner->fresh()->nif);
    }

    public function test_campos_sensiveis_no_body_sao_ignorados(): void
    {
        $this->editar(['active' => false, 'user_id' => $this->morador->id])->assertOk();

        $this->assertSame($this->parceiro->id, $this->parceiro->partner->fresh()->user_id);

        // continua a aparecer na lista
        $lista = collect($this->actingAs($this->morador)->getJson('/api/v1/partners')->json('data'));
        $this->assertTrue($lista->contains('id', $this->idParceiro()));
    }

    public function test_so_se_edita_um_perfil_de_parceiro_que_se_tem(): void
    {
        // o morador não é parceiro
        $this->actingAs($this->morador)
            ->putJson('/user/profile-information', ['role' => 'partner', 'phone' => '900000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    // ---------------------------------------- remoção (DELETE /api/user/profile)

    public function test_o_dono_apaga_o_perfil_as_ofertas_e_perde_a_role(): void
    {
        $id = $this->idParceiro();
        $this->parceiro->assignRole('resident'); // para não ser o único perfil da conta

        $this->actingAs($this->parceiro)
            ->deleteJson('/api/user/profile', ['role' => 'partner'])
            ->assertOk();

        $this->assertSoftDeleted('partners', ['id' => $id]);
        $this->assertSame(0, Offer::where('partner_id', $id)->count());
        $this->assertFalse($this->parceiro->fresh()->hasRole('partner'));

        // deixa de aparecer, e as ofertas saem das recomendações
        $this->actingAs($this->morador)->getJson("/api/v1/partners/{$id}")->assertNotFound();

        $recomendacoes = collect($this->actingAs($this->morador)->getJson('/api/v1/offers/recommendations')->json('data'));
        $this->assertFalse($recomendacoes->contains(fn ($o) => $o['partner']['id'] === $id));
    }

    public function test_nao_se_apaga_o_unico_perfil_da_conta(): void
    {
        $this->actingAs($this->parceiro)
            ->deleteJson('/api/user/profile', ['role' => 'partner'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role' => 'Não pode remover o seu único perfil. Elimine a conta.']);

        $this->assertNotNull($this->parceiro->fresh()->partner);
    }

    // ------------------------------------------------------- rotas removidas

    public function test_as_rotas_antigas_ja_nao_existem(): void
    {
        $id = $this->idParceiro();

        $this->actingAs($this->morador)->getJson("/api/v1/partners/offers/{$id}")->assertNotFound();
        $this->actingAs($this->morador)->getJson("/api/v1/offers/partner/{$id}")->assertNotFound();

        // criar, editar e apagar o perfil passou para os endpoints dos perfis (o URL existe para GET: 405)
        $this->actingAs($this->parceiro)->postJson('/api/v1/partners', [])->assertStatus(405);
        $this->actingAs($this->parceiro)->patchJson("/api/v1/partners/{$id}", ['phone' => '900000000'])->assertStatus(405);
        $this->actingAs($this->parceiro)->deleteJson("/api/v1/partners/{$id}")->assertStatus(405);
    }
}