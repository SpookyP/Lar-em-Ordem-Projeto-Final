<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Prestadores de serviços (módulo 6): listagem, perfil próprio, detalhe,
 * criação e edição com zonas e especialidades num só pedido, e remoção.
 * Usa os dados do ServiceProviderSeeder ("Serviços Lda" = service@example.com).
 */
class ServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $morador;
    private User $servicos;
    private int $canalizacao;
    /** @var array<int> */
    private array $zonas;
    /** @var array<int> */
    private array $especialidades;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->morador  = User::where('email', 'morador@example.com')->firstOrFail();
        $this->servicos = User::where('email', 'service@example.com')->firstOrFail();

        $this->canalizacao    = ProblemCategory::where('name', 'Canalização')->value('id');
        $this->zonas          = ServiceZone::where('county', 'Matosinhos')->orderBy('id')->limit(3)->pluck('id')->all();
        $this->especialidades = ServiceSpecialty::where('problem_category_id', $this->canalizacao)->orderBy('id')->limit(3)->pluck('id')->all();
    }

    // ---------------------------------------------------------------- helpers

    private function dadosPrestador(array $extra = []): array
    {
        return $extra + [
            'company_name' => 'Reparações Lda',
            'nif'          => '123456789',
            'phone'        => '912345678',
            'description'  => 'Pequenas reparações.',
        ];
    }

    private function idServicos(): string
    {
        return $this->servicos->service_provider->id;
    }

    // ------------------------------------------------------------ index / me

    public function test_index_lista_os_prestadores_com_zonas_e_especialidades_sem_nif(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson('/api/v1/service-providers')
            ->assertOk()
            ->assertJsonCount(ServiceProviderModel::count(), 'data');

        foreach ($resposta->json('data') as $prestador) {
            $this->assertArrayHasKey('zones', $prestador);
            $this->assertArrayHasKey('specialties', $prestador);
            $this->assertArrayNotHasKey('nif', $prestador);
        }
    }

    public function test_no_index_o_prestador_so_ve_o_seu_proprio_nif(): void
    {
        $lista = collect(
            $this->actingAs($this->servicos)->getJson('/api/v1/service-providers')->assertOk()->json('data')
        );

        $this->assertSame('212345673', $lista->firstWhere('id', $this->idServicos())['nif']);
        $this->assertArrayNotHasKey('nif', $lista->firstWhere('id', '!=', $this->idServicos()));
    }

    public function test_me_devolve_o_perfil_do_prestador_autenticado(): void
    {
        $this->actingAs($this->servicos)
            ->getJson('/api/v1/service-providers/me')
            ->assertOk()
            ->assertJsonPath('data.id', $this->idServicos())
            ->assertJsonPath('data.company_name', 'Serviços Lda')
            ->assertJsonPath('data.nif', '212345673')
            ->assertJsonCount(4, 'data.specialties');
    }

    public function test_me_sem_perfil_de_prestador_da_403(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/service-providers/me')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------- show

    public function test_show_mostra_o_prestador_sem_nif_a_terceiros(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/service-providers/' . $this->idServicos())
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Serviços Lda')
            ->assertJsonMissingPath('data.nif')
            ->assertJsonStructure(['data' => [
                'specialties' => [['id', 'display_name', 'is_custom', 'category']],
                'zones'       => [['id', 'district', 'county', 'location']],
            ]]);
    }

    public function test_o_campo_active_ja_nao_existe(): void
    {
        $this->actingAs($this->servicos)
            ->getJson('/api/v1/service-providers/me')
            ->assertJsonMissingPath('data.active');
    }

    // ------------------------------------------------------------------ store

    public function test_cria_o_perfil_sem_zonas_nem_especialidades(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/service-providers', $this->dadosPrestador())
            ->assertCreated()
            ->assertJsonPath('data.nif', '123456789')
            ->assertJsonCount(0, 'data.zones')
            ->assertJsonCount(0, 'data.specialties');

        $this->assertTrue($user->fresh()->hasRole('service_provider'));
    }

    public function test_cria_o_perfil_com_zonas_e_especialidades_num_so_pedido(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/service-providers', $this->dadosPrestador([
                'zone_ids'              => [$this->zonas[0], $this->zonas[1]],
                'service_specialty_ids' => [$this->especialidades[0], $this->especialidades[1]],
                'custom_specialties'    => [['problem_category_id' => $this->canalizacao, 'name' => 'Pladur']],
            ]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.zones')
            ->assertJsonCount(3, 'data.specialties');

        $this->assertDatabaseHas('provider_specialties', [
            'service_provider_id' => $user->service_provider->id,
            'custom_name'         => 'Pladur',
        ]);
    }

    public function test_o_user_id_nao_vem_do_body(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/service-providers', $this->dadosPrestador(['user_id' => $this->morador->id]))
            ->assertCreated();

        $this->assertNotNull($user->fresh()->service_provider);
        $this->assertNull($this->morador->fresh()->service_provider);
    }

    public function test_cada_utilizador_so_tem_um_perfil_de_prestador(): void
    {
        $this->actingAs($this->servicos)
            ->postJson('/api/v1/service-providers', $this->dadosPrestador())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id' => 'Este utilizador já tem um perfil de prestador de serviços.']);
    }

    public function test_um_erro_nas_especialidades_desfaz_a_criacao_toda(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/service-providers', $this->dadosPrestador([
                'custom_specialties' => [
                    ['problem_category_id' => $this->canalizacao, 'name' => 'Pladur'],
                    ['problem_category_id' => $this->canalizacao, 'name' => 'pladur'],
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['custom_specialties' => 'A lista de especialidades próprias tem valores repetidos.']);

        $user->refresh();
        $this->assertNull($user->service_provider);
        $this->assertFalse($user->hasRole('service_provider'));
    }

    public function test_store_valida_os_campos_e_as_listas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/service-providers', [
                'nif'                   => '123',
                'zone_ids'              => [999999],
                'service_specialty_ids' => [$this->especialidades[0], $this->especialidades[0]],
                'custom_specialties'    => [['name' => 'Sem categoria']],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'company_name',
                'nif',
                'zone_ids.0'                             => 'Uma das zonas indicadas não existe.',
                'service_specialty_ids.0'                => 'A lista de especialidades tem valores repetidos.',
                'custom_specialties.0.problem_category_id' => 'Indique a categoria da especialidade própria.',
            ]);
    }

    // ----------------------------------------------------------------- update

    public function test_mudar_so_um_campo_nao_mexe_nas_listas(): void
    {
        $this->actingAs($this->servicos)
            ->patchJson('/api/v1/service-providers/' . $this->idServicos(), ['phone' => '911111111'])
            ->assertOk()
            ->assertJsonPath('data.phone', '911111111')
            ->assertJsonCount($this->servicos->service_provider->zones()->count(), 'data.zones')
            ->assertJsonCount(4, 'data.specialties');
    }

    public function test_as_listas_enviadas_substituem_as_anteriores_e_mantem_os_ids(): void
    {
        $prestador = $this->servicos->service_provider;

        // o "Serviços Lda" já tem "Desentupimentos"; o id dessa linha deve manter-se
        $desentupimentos = ServiceSpecialty::where('name', 'Desentupimentos')->value('id');
        $idAntes = $prestador->specialties()->where('service_specialty_id', $desentupimentos)->value('id');

        $resposta = $this->actingAs($this->servicos)
            ->patchJson('/api/v1/service-providers/' . $prestador->id, [
                'zone_ids'              => [$this->zonas[2]],
                'service_specialty_ids' => [$desentupimentos],
                'custom_specialties'    => [['problem_category_id' => $this->canalizacao, 'name' => 'Pladur']],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.zones')
            ->assertJsonPath('data.zones.0.id', $this->zonas[2])
            ->assertJsonCount(2, 'data.specialties');

        $ids = collect($resposta->json('data.specialties'))->pluck('id');
        $this->assertTrue($ids->contains($idAntes));
    }

    public function test_listas_vazias_retiram_tudo_dessa_lista(): void
    {
        $this->actingAs($this->servicos)
            ->patchJson('/api/v1/service-providers/' . $this->idServicos(), [
                'zone_ids'              => [],
                'service_specialty_ids' => [],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data.zones')
            ->assertJsonCount(0, 'data.specialties');
    }

    public function test_um_erro_no_update_nao_grava_nada(): void
    {
        $this->actingAs($this->servicos)
            ->patchJson('/api/v1/service-providers/' . $this->idServicos(), [
                'company_name'       => 'Não deve gravar',
                'custom_specialties' => [
                    ['problem_category_id' => $this->canalizacao, 'name' => 'A'],
                    ['problem_category_id' => $this->canalizacao, 'name' => 'a'],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame('Serviços Lda', $this->servicos->service_provider->fresh()->company_name);
    }

    public function test_o_active_enviado_no_body_e_ignorado(): void
    {
        $this->actingAs($this->servicos)
            ->patchJson('/api/v1/service-providers/' . $this->idServicos(), ['active' => false, 'phone' => '922222222'])
            ->assertOk()
            ->assertJsonPath('data.phone', '922222222')
            ->assertJsonMissingPath('data.active');
    }

    public function test_so_o_dono_edita_o_perfil(): void
    {
        $outro = User::where('email', 'residente@example.com')->firstOrFail();

        $this->actingAs($outro)
            ->patchJson('/api/v1/service-providers/' . $this->idServicos(), ['phone' => '900000000'])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- destroy

    public function test_o_dono_apaga_o_perfil_e_perde_a_role(): void
    {
        $id = $this->idServicos();

        $this->actingAs($this->servicos)
            ->deleteJson("/api/v1/service-providers/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Prestador de serviços removido');

        $this->assertSoftDeleted('service_providers', ['id' => $id]);
        $this->assertFalse($this->servicos->fresh()->hasRole('service_provider'));

        // deixa de aparecer e deixa de existir para a API
        $this->actingAs($this->morador)->getJson("/api/v1/service-providers/{$id}")->assertNotFound();
        $lista = collect($this->actingAs($this->morador)->getJson('/api/v1/service-providers')->json('data'));
        $this->assertFalse($lista->contains('id', $id));
    }

    public function test_so_o_dono_apaga_o_perfil(): void
    {
        $this->actingAs($this->morador)
            ->deleteJson('/api/v1/service-providers/' . $this->idServicos())
            ->assertForbidden();
    }

    // ------------------------------------------------------- rotas removidas

    public function test_as_rotas_antigas_ja_nao_existem(): void
    {
        $id = $this->idServicos();

        $this->actingAs($this->servicos)->getJson('/api/v1/service-providers/active')->assertNotFound();
        $this->actingAs($this->servicos)->putJson("/api/v1/service-providers/{$id}/zones", ['zone_ids' => []])->assertNotFound();
        $this->actingAs($this->servicos)->postJson("/api/v1/service-providers/{$id}/specialties", [])->assertNotFound();
    }
}