<?php

namespace Tests\Feature\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Condomínio (módulo 8): CRUD do administrador, código de convite (join/leave),
 * página do residente, transferência de administrador e perfil de administrador.
 * Usa o CondominiumSeeder: condominio@example.com administra o "Edifício Brito Capelo" (código CAPELA23),
 * onde está a casa de Matosinhos do morador@example.com.
 */
class CondominiumTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;
    private User $morador;
    private User $residente;
    private Condominium $condominio;
    private string $casa;       // casa do morador no prédio
    private string $outraCasa;  // outra casa do morador, sem condomínio

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->admin      = User::where('email', 'condominio@example.com')->firstOrFail();
        $this->morador    = User::where('email', 'morador@example.com')->firstOrFail();
        $this->residente  = User::where('email', 'residente@example.com')->firstOrFail();
        $this->condominio = Condominium::where('name', 'Edifício Brito Capelo')->firstOrFail();
        $this->casa       = $this->condominio->properties()->value('id');
        $this->outraCasa  = Property::whereNull('condominium_id')
            ->whereHas('contracts', fn ($q) => $q->where('resident_id', $this->morador->resident->id)->where('is_active', true))
            ->value('id');
    }

    // ---------------------------------------------------------------- helpers

    private function novoAdmin(): User
    {
        $user = User::factory()->create();
        CondominiumAdmin::factory()->create(['user_id' => $user->id]);
        $user->assignRole('condominium_admin');

        return $user;
    }

    private function morada(array $override = []): array
    {
        return $override + [
            'street'      => 'Rua de Cedofeita',
            'postal_code' => '4050-180',
            'county'      => 'Porto',
            'location'    => 'Cedofeita',
            'district'    => 'Porto',
        ];
    }

    // ----------------------------------------------------------------- criar

    public function test_o_administrador_cria_um_condominio_com_codigo_de_convite(): void
    {
        $resposta = $this->actingAs($this->admin)
            ->postJson('/api/v1/condominiums', ['name' => 'Edifício Novo', 'reserve_fund' => 1500, 'address' => $this->morada()])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Edifício Novo')
            ->assertJsonPath('data.can_manage', true)
            ->assertJsonPath('data.properties_count', 0)
            ->assertJsonPath('data.address.county', 'Porto');

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $resposta->json('data.invite_code'));
        $this->assertSame(
            $this->admin->condominium_admin->id,
            Condominium::find($resposta->json('data.id'))->condominium_admin_id
        );
    }

    public function test_o_administrador_e_o_codigo_nunca_vem_do_pedido(): void
    {
        $outro = $this->novoAdmin();

        $id = $this->actingAs($this->admin)
            ->postJson('/api/v1/condominiums', [
                'name'                 => 'Edifício Novo',
                'address'              => $this->morada(),
                'invite_code'          => 'AAAAAAAA',
                'condominium_admin_id' => $outro->condominium_admin->id,
            ])
            ->assertCreated()
            ->json('data.id');

        $criado = Condominium::find($id);
        $this->assertNotSame('AAAAAAAA', $criado->invite_code);
        $this->assertSame($this->admin->condominium_admin->id, $criado->condominium_admin_id);
    }

    public function test_a_morada_tem_de_existir_nas_zonas(): void
    {
        // concelho de outro distrito
        $this->actingAs($this->admin)
            ->postJson('/api/v1/condominiums', ['name' => 'X', 'address' => $this->morada(['county' => 'Matosinhos', 'district' => 'Lisboa'])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address.county' => 'O concelho indicado não existe nesse distrito.']);

        // distrito inventado
        $this->actingAs($this->admin)
            ->postJson('/api/v1/condominiums', ['name' => 'X', 'address' => $this->morada(['district' => 'Bairrada'])])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address.district' => 'O distrito indicado não existe.']);
    }

    public function test_so_quem_tem_perfil_de_administrador_cria(): void
    {
        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums', ['name' => 'X', 'address' => $this->morada()])
            ->assertForbidden();
    }

    // ------------------------------------------------------------ listar/ver

    public function test_o_administrador_lista_so_os_seus_condominios(): void
    {
        Condominium::factory()->create(); // de outro administrador

        $this->actingAs($this->admin)
            ->getJson('/api/v1/condominiums')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Edifício Brito Capelo')
            ->assertJsonPath('data.0.properties_count', 1);
    }

    public function test_show_traz_as_fracoes_o_codigo_e_os_avisos_em_vigor_com_leituras(): void
    {
        $resposta = $this->actingAs($this->admin)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}")
            ->assertOk()
            ->assertJsonPath('data.invite_code', 'CAPELA23')
            ->assertJsonPath('data.properties.0.id', $this->casa)
            ->assertJsonCount(2, 'data.notices'); // o expirado não aparece

        // prioridade alta primeiro; o administrador vê quantos residentes já leram
        $this->assertSame('Corte de água', $resposta->json('data.notices.0.title'));
        $this->assertSame(1, $resposta->json('data.notices.1.readers_count'));
    }

    public function test_o_residente_nao_ve_o_condominio_pelo_id(): void
    {
        $this->actingAs($this->morador)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}")
            ->assertForbidden();
    }

    public function test_o_residente_ve_o_condominio_pela_casa_sem_dados_de_gestao(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$this->casa}/condominium")
            ->assertOk()
            ->assertJsonPath('data.name', 'Edifício Brito Capelo')
            ->assertJsonPath('data.can_manage', false)
            ->assertJsonPath('data.admin.name', 'administrador')
            ->assertJsonCount(2, 'data.notices')
            ->assertJsonMissingPath('data.invite_code')
            ->assertJsonMissingPath('data.properties');

        $this->assertArrayNotHasKey('readers_count', $resposta->json('data.notices.0'));
    }

    public function test_casa_sem_condominio_devolve_null(): void
    {
        $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$this->outraCasa}/condominium")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_ninguem_ve_o_condominio_da_casa_de_outra_pessoa(): void
    {
        $this->actingAs($this->residente)
            ->getJson("/api/v1/properties/{$this->casa}/condominium")
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- editar

    public function test_gerar_novo_codigo_invalida_o_antigo(): void
    {
        $novo = $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}", ['regenerate_invite_code' => true])
            ->assertOk()
            ->json('data.invite_code');

        $this->assertNotSame('CAPELA23', $novo);

        // a casa que já estava continua; o código antigo deixa de funcionar
        $this->assertSame($this->condominio->id, Property::find($this->casa)->condominium_id);
        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums/join', ['invite_code' => 'CAPELA23', 'property_id' => $this->outraCasa])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['invite_code' => 'Código de convite inválido.']);
    }

    public function test_editar_a_morada_valida_o_par_distrito_concelho(): void
    {
        $url = "/api/v1/condominiums/{$this->condominio->id}";

        $this->actingAs($this->admin)
            ->patchJson($url, ['address' => ['district' => 'Lisboa', 'county' => 'Matosinhos']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address.county']);

        // só o concelho, sem o distrito
        $this->actingAs($this->admin)
            ->patchJson($url, ['address' => ['county' => 'Porto']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['address.district' => 'Indique o distrito juntamente com o concelho.']);

        $this->actingAs($this->admin)
            ->patchJson($url, ['name' => 'Edifício Capelo', 'address' => ['district' => 'Porto', 'county' => 'Porto', 'location' => 'Bonfim']])
            ->assertOk()
            ->assertJsonPath('data.name', 'Edifício Capelo')
            ->assertJsonPath('data.address.location', 'Bonfim')
            ->assertJsonPath('data.address.street', 'Rua Brito Capelo');
    }

    public function test_o_administrador_so_muda_pela_transferencia(): void
    {
        $outro = $this->novoAdmin();

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}", ['condominium_admin_id' => $outro->condominium_admin->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['condominium_admin_id' => 'Para mudar de administrador use a transferência do condomínio.']);
    }

    public function test_outro_administrador_nao_edita_nem_apaga(): void
    {
        $outro = $this->novoAdmin();
        $url = "/api/v1/condominiums/{$this->condominio->id}";

        $this->actingAs($outro)->patchJson($url, ['name' => 'X'])->assertForbidden();
        $this->actingAs($outro)->deleteJson($url)->assertForbidden();
    }

    // ---------------------------------------------------------------- apagar

    public function test_apagar_o_condominio_desliga_as_casas(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/condominiums/{$this->condominio->id}")
            ->assertOk();

        $this->assertSoftDeleted($this->condominio);
        $this->assertNull(Property::find($this->casa)->condominium_id);
    }

    // ------------------------------------------------------------ join/leave

    public function test_o_residente_junta_uma_casa_com_o_codigo(): void
    {
        // o código não distingue maiúsculas/minúsculas
        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums/join', ['invite_code' => 'capela23', 'property_id' => $this->outraCasa])
            ->assertOk()
            ->assertJsonPath('data.id', $this->condominio->id)
            ->assertJsonMissingPath('data.invite_code');

        $this->assertSame($this->condominio->id, Property::find($this->outraCasa)->condominium_id);
    }

    public function test_join_recusa_casas_que_ja_pertencem_a_um_condominio(): void
    {
        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums/join', ['invite_code' => 'CAPELA23', 'property_id' => $this->casa])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'Esta casa já pertence a este condomínio.']);

        $outro = Condominium::factory()->create();
        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums/join', ['invite_code' => $outro->invite_code, 'property_id' => $this->casa])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'Esta casa já pertence a outro condomínio. Saia primeiro desse condomínio.']);
    }

    public function test_join_so_com_casas_onde_tem_contrato_ativo(): void
    {
        $this->actingAs($this->residente)
            ->postJson('/api/v1/condominiums/join', ['invite_code' => 'CAPELA23', 'property_id' => $this->outraCasa])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'Só pode ligar ao condomínio casas onde tem um contrato ativo.']);
    }

    public function test_o_residente_retira_a_sua_casa(): void
    {
        $this->actingAs($this->morador)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}/leave", ['property_id' => $this->casa])
            ->assertOk();

        $this->assertNull(Property::find($this->casa)->condominium_id);
    }

    public function test_o_administrador_retira_qualquer_casa_do_seu_condominio(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}/leave", ['property_id' => $this->casa])
            ->assertOk();

        $this->assertNull(Property::find($this->casa)->condominium_id);

        // uma casa que não é do condomínio
        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}/leave", ['property_id' => $this->outraCasa])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_quem_nao_e_do_predio_nao_retira_casas(): void
    {
        $this->actingAs($this->residente)
            ->patchJson("/api/v1/condominiums/{$this->condominio->id}/leave", ['property_id' => $this->casa])
            ->assertForbidden();

        $this->assertSame($this->condominio->id, Property::find($this->casa)->condominium_id);
    }

    // ---------------------------------------------------------- transferência

    public function test_transferencia_passa_o_condominio_e_os_ficheiros_para_o_novo_administrador(): void
    {
        $novo = $this->novoAdmin();
        $documento = CondominiumDocument::factory()->create(['condominium_id' => $this->condominio->id]);

        $codigo = $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/transfer")
            ->assertCreated()
            ->json('data.transfer_code');

        $this->assertSame(12, strlen($codigo));
        $this->assertNotNull($this->condominio->fresh()->transfer_expires_at);

        $this->actingAs($novo)
            ->postJson('/api/v1/condominiums/claim', ['transfer_code' => strtolower($codigo)])
            ->assertOk()
            ->assertJsonPath('data.can_manage', true);

        $condominio = $this->condominio->fresh();
        $this->assertSame($novo->condominium_admin->id, $condominio->condominium_admin_id);
        $this->assertNull($condominio->transfer_expires_at);
        $this->assertSame($novo->id, $documento->file->fresh()->user_id);

        // o antigo deixa de ter acesso
        $this->actingAs($this->admin)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}")
            ->assertForbidden();
    }

    public function test_codigo_de_transferencia_expirado_ou_cancelado_nao_funciona(): void
    {
        $novo = $this->novoAdmin();
        Condominium::factory()->withTransferCode('EXPIRADO2345', -1)->create();

        $this->actingAs($novo)
            ->postJson('/api/v1/condominiums/claim', ['transfer_code' => 'EXPIRADO2345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transfer_code' => 'Código de transferência inválido ou expirado.']);

        $codigo = $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/transfer")
            ->json('data.transfer_code');
        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/condominiums/{$this->condominio->id}/transfer")
            ->assertOk();

        $this->actingAs($novo)
            ->postJson('/api/v1/condominiums/claim', ['transfer_code' => $codigo])
            ->assertStatus(422);
    }

    public function test_o_proprio_administrador_nao_reclama_o_seu_condominio(): void
    {
        $codigo = $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/transfer")
            ->json('data.transfer_code');

        $this->actingAs($this->admin)
            ->postJson('/api/v1/condominiums/claim', ['transfer_code' => $codigo])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transfer_code' => 'Já é o administrador deste condomínio.']);
    }

    public function test_so_o_administrador_atual_inicia_a_transferencia_e_so_administradores_reclamam(): void
    {
        $this->actingAs($this->novoAdmin())
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/transfer")
            ->assertForbidden();

        $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums/claim', ['transfer_code' => 'ABCDEFGH2345'])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------ perfil

    public function test_o_perfil_de_administrador_so_sai_depois_de_transferir_ou_apagar_os_condominios(): void
    {
        // o morador também passa a administrar um prédio (tem dois perfis)
        $this->actingAs($this->morador)
            ->postJson('/api/user/profile', ['role' => 'condominium_admin', 'contact' => '912345678', 'admin_type' => 'resident'])
            ->assertCreated();

        $id = $this->actingAs($this->morador)
            ->postJson('/api/v1/condominiums', ['name' => 'Prédio do Morador', 'address' => $this->morada()])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($this->morador)
            ->deleteJson('/api/user/profile', ['role' => 'condominium_admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role' => 'Transfira primeiro os condomínios que administra.']);

        $this->actingAs($this->morador)->deleteJson("/api/v1/condominiums/{$id}")->assertOk();

        $this->actingAs($this->morador)
            ->deleteJson('/api/user/profile', ['role' => 'condominium_admin'])
            ->assertOk();

        $this->assertFalse($this->morador->fresh()->hasRole('condominium_admin'));
    }
}
