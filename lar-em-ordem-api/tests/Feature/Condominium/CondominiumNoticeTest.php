<?php

namespace Tests\Feature\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumNotice;
use App\Models\Property\Property;
use App\Models\User\User;
use App\Notifications\Condominium\CondominiumNoticePublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Avisos do condomínio (módulo 8): publicação com email, edição, histórico do administrador
 * e o "sino" do residente (lido, marcar tudo, eliminar).
 * Usa o CondominiumSeeder: no "Edifício Brito Capelo" há "Corte de água" (alta, por ler),
 * "Limpeza das escadas" (normal, lida pelo morador) e "Manutenção do elevador" (expirado).
 */
class CondominiumNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;
    private User $morador;
    private User $residente;
    private Condominium $condominio;
    private string $casa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->admin      = User::where('email', 'condominio@example.com')->firstOrFail();
        $this->morador    = User::where('email', 'morador@example.com')->firstOrFail();
        $this->residente  = User::where('email', 'residente@example.com')->firstOrFail();
        $this->condominio = Condominium::where('name', 'Edifício Brito Capelo')->firstOrFail();
        $this->casa       = $this->condominio->properties()->value('id');
    }

    // ---------------------------------------------------------------- helpers

    private function aviso(string $titulo): CondominiumNotice
    {
        return $this->condominio->notices()->where('title', $titulo)->firstOrFail();
    }

    private function sino(): array
    {
        return $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$this->casa}/condominium-notices")
            ->assertOk()
            ->json('data');
    }

    private function outroAdmin(): User
    {
        $user = User::factory()->create();
        CondominiumAdmin::factory()->create(['user_id' => $user->id]);
        $user->assignRole('condominium_admin');

        return $user;
    }

    // ------------------------------------------------------------- publicar

    public function test_publicar_envia_email_so_aos_residentes_do_predio(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/notices", [
                'title'      => 'Pintura da fachada',
                'message'    => 'Começa na segunda-feira.',
                'priority'   => 'high',
                'expires_at' => now()->addWeek()->toDateTimeString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.readers_count', 0);

        Notification::assertSentTo($this->morador, CondominiumNoticePublished::class);
        Notification::assertNotSentTo($this->residente, CondominiumNoticePublished::class);
        Notification::assertSentTimes(CondominiumNoticePublished::class, 1);

        // aparece no sino por ler
        $this->assertFalse(collect($this->sino())->firstWhere('title', 'Pintura da fachada')['read']);
    }

    public function test_publicar_valida_os_campos(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio->id}/notices", [
                'priority'   => 'urgente',
                'expires_at' => now()->subDay()->toDateTimeString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title'      => 'Indique o título do aviso.',
                'message'    => 'Escreva a mensagem do aviso.',
                'priority'   => 'A prioridade tem de ser "low", "normal" ou "high".',
                'expires_at' => 'A data de fim do aviso tem de ser no futuro.',
            ]);
    }

    public function test_so_o_administrador_do_condominio_publica(): void
    {
        $url = "/api/v1/condominiums/{$this->condominio->id}/notices";
        $dados = ['title' => 'X', 'message' => 'Y'];

        $this->actingAs($this->morador)->postJson($url, $dados)->assertForbidden();
        $this->actingAs($this->outroAdmin())->postJson($url, $dados)->assertForbidden();
    }

    // ------------------------------------------------------------- histórico

    public function test_o_historico_inclui_os_expirados_e_quantos_leram(): void
    {
        $avisos = collect($this->actingAs($this->admin)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}/notices")
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->json('data'))->keyBy('title');

        $this->assertArrayHasKey('Manutenção do elevador', $avisos);
        $this->assertSame(1, $avisos['Limpeza das escadas']['readers_count']);
        $this->assertSame(0, $avisos['Corte de água']['readers_count']);
    }

    public function test_o_residente_nao_ve_o_historico(): void
    {
        $this->actingAs($this->morador)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}/notices")
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- editar

    public function test_editar_sem_alteracoes_nao_reenvia_nem_limpa_as_leituras(): void
    {
        Notification::fake();
        $aviso = $this->aviso('Limpeza das escadas');

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominium-notices/{$aviso->id}", ['title' => 'Limpeza das escadas'])
            ->assertOk()
            ->assertJsonPath('data.readers_count', 1);

        Notification::assertNothingSent();
    }

    public function test_editar_com_alteracoes_reenvia_e_volta_a_ficar_por_ler(): void
    {
        Notification::fake();
        $aviso = $this->aviso('Limpeza das escadas');

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominium-notices/{$aviso->id}", ['message' => 'Passa a ser às sextas-feiras.'])
            ->assertOk()
            ->assertJsonPath('data.readers_count', 0);

        Notification::assertSentTo($this->morador, CondominiumNoticePublished::class);
        $this->assertFalse(collect($this->sino())->firstWhere('title', 'Limpeza das escadas')['read']);
    }

    public function test_editar_um_aviso_expirado_nao_envia_email(): void
    {
        Notification::fake();
        $aviso = $this->aviso('Manutenção do elevador');

        $this->actingAs($this->admin)
            ->patchJson("/api/v1/condominium-notices/{$aviso->id}", ['title' => 'Manutenção do elevador (concluída)'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_o_administrador_apaga_um_aviso(): void
    {
        $aviso = $this->aviso('Corte de água');

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/condominium-notices/{$aviso->id}")
            ->assertOk();

        $this->assertModelMissing($aviso);
        $this->assertNull(collect($this->sino())->firstWhere('title', 'Corte de água'));
    }

    public function test_so_o_administrador_do_condominio_edita_e_apaga(): void
    {
        $url = "/api/v1/condominium-notices/{$this->aviso('Corte de água')->id}";

        foreach ([$this->morador, $this->outroAdmin()] as $user) {
            $this->actingAs($user)->patchJson($url, ['title' => 'X'])->assertForbidden();
            $this->actingAs($user)->deleteJson($url)->assertForbidden();
        }
    }

    // ------------------------------------------------------------------ sino

    public function test_o_sino_mostra_os_avisos_em_vigor_com_read(): void
    {
        $sino = $this->sino();

        // mais recentes primeiro; o expirado não aparece
        $this->assertSame(['Corte de água', 'Limpeza das escadas'], array_column($sino, 'title'));
        $this->assertSame([false, true], array_column($sino, 'read'));
    }

    public function test_dar_o_visto_marca_como_lido_e_repetir_nao_muda_a_data(): void
    {
        $aviso = $this->aviso('Corte de água');
        $url = "/api/v1/condominium-notices/{$aviso->id}/read";

        $this->actingAs($this->morador)->patchJson($url)->assertOk()->assertJsonPath('data.read', true);
        $primeiro = $aviso->readers()->first()->pivot->read_at;

        $this->travel(1)->hours();
        $this->actingAs($this->morador)->patchJson($url)->assertOk();

        $this->assertEquals($primeiro, $aviso->readers()->first()->pivot->read_at);
        $this->assertSame([true, true], array_column($this->sino(), 'read'));
    }

    public function test_marcar_tudo_como_lido(): void
    {
        $url = "/api/v1/properties/{$this->casa}/condominium-notices/read-all";

        $this->actingAs($this->morador)->patchJson($url)->assertOk()->assertJsonPath('updated_count', 1);
        $this->actingAs($this->morador)->patchJson($url)->assertOk()->assertJsonPath('updated_count', 0);

        $this->assertSame([true, true], array_column($this->sino(), 'read'));
    }

    public function test_eliminar_do_sino_nao_apaga_o_aviso(): void
    {
        $aviso = $this->aviso('Corte de água');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/condominium-notices/{$aviso->id}/dismiss")
            ->assertOk();

        $this->assertSame(['Limpeza das escadas'], array_column($this->sino(), 'title'));

        // continua na página do condomínio e no histórico (já como lido)
        $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$this->casa}/condominium")
            ->assertJsonCount(2, 'data.notices');
        $this->assertSame(1, $aviso->readers()->count());
    }

    public function test_casa_sem_condominio_tem_o_sino_vazio(): void
    {
        $outraCasa = Property::whereNull('condominium_id')
            ->whereHas('contracts', fn ($q) => $q->where('resident_id', $this->morador->resident->id)->where('is_active', true))
            ->value('id');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$outraCasa}/condominium-notices")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/properties/{$outraCasa}/condominium-notices/read-all")
            ->assertOk()
            ->assertJsonPath('updated_count', 0);
    }

    public function test_quem_nao_e_do_predio_nao_ve_nem_marca_avisos(): void
    {
        $aviso = $this->aviso('Corte de água');

        $this->actingAs($this->residente)->getJson("/api/v1/properties/{$this->casa}/condominium-notices")->assertForbidden();
        $this->actingAs($this->residente)->patchJson("/api/v1/condominium-notices/{$aviso->id}/read")->assertForbidden();
        $this->actingAs($this->residente)->patchJson("/api/v1/condominium-notices/{$aviso->id}/dismiss")->assertForbidden();
    }
}
