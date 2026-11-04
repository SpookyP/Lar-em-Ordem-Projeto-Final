<?php

namespace Tests\Feature\Condominium;

use App\Events\Condominium\CondominiumNoticeBroadcast;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Avisos do condomínio em tempo real (Reverb): canal privado por casa e evento ao publicar/editar.
 */
class CondominiumBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $morador;
    private User $admin;
    private string $casa;
    private string $condominio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->morador = User::where('email', 'morador@example.com')->firstOrFail();
        $this->casa = AssistanceRequest::where('title', 'Sanita entupida')->value('property_id');

        $this->admin = User::factory()->create();
        CondominiumAdmin::factory()->create(['user_id' => $this->admin->id]);
        $this->admin->assignRole('condominium_admin');

        $criado = $this->actingAs($this->admin)->postJson('/api/v1/condominiums', [
            'name'    => 'Edifício Atlântico',
            'address' => ['street' => 'Rua A', 'postal_code' => '4450-001', 'county' => 'Matosinhos', 'location' => 'Matosinhos', 'district' => 'Porto'],
        ])->assertCreated();

        $this->condominio = $criado->json('data.id');

        $this->actingAs($this->morador)->postJson('/api/v1/condominiums/join', [
            'invite_code' => $criado->json('data.invite_code'),
            'property_id' => $this->casa,
        ])->assertOk();
    }

    /** Pede autorização para o canal privado de uma casa, como o Echo faz. */
    private function autorizarCanal(User $user, string $propertyId)
    {
        // o driver null não assina a autorização: muda para reverb e volta a registar os canais nele
        config(['broadcasting.default' => 'reverb']);
        require base_path('routes/channels.php');

        return $this->actingAs($user)->postJson('/api/broadcasting/auth', [
            'socket_id'    => '1234.5678',
            'channel_name' => "private-properties.{$propertyId}",
        ]);
    }

    public function test_o_residente_pode_ouvir_o_canal_da_sua_casa(): void
    {
        $this->autorizarCanal($this->morador, $this->casa)
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_ninguem_ouve_o_canal_da_casa_de_outra_pessoa(): void
    {
        $outro = User::where('email', 'residente@example.com')->firstOrFail();

        $this->autorizarCanal($outro, $this->casa)->assertForbidden();
    }

    public function test_publicar_um_aviso_envia_o_evento_para_o_canal_das_casas_do_predio(): void
    {
        Event::fake([CondominiumNoticeBroadcast::class]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio}/notices", ['title' => 'Corte de água', 'message' => 'Dia 5.'])
            ->assertCreated();

        Event::assertDispatched(CondominiumNoticeBroadcast::class, function (CondominiumNoticeBroadcast $event) {
            $canais = collect($event->broadcastOn())->map->name->all();

            return $canais === ["private-properties.{$this->casa}"]
                && $event->broadcastAs() === 'condominium.notice'
                && $event->broadcastWith()['title'] === 'Corte de água'
                && $event->broadcastWith()['updated'] === false;
        });
    }

    public function test_editar_um_aviso_volta_a_enviar_o_evento_so_se_mudou(): void
    {
        $aviso = $this->actingAs($this->admin)
            ->postJson("/api/v1/condominiums/{$this->condominio}/notices", ['title' => 'Corte de água', 'message' => 'Dia 5.'])
            ->json('data.id');

        Event::fake([CondominiumNoticeBroadcast::class]);

        // sem alterações: nada
        $this->actingAs($this->admin)->patchJson("/api/v1/condominium-notices/{$aviso}", ['title' => 'Corte de água'])->assertOk();
        Event::assertNotDispatched(CondominiumNoticeBroadcast::class);

        // com alterações: evento com updated = true
        $this->actingAs($this->admin)->patchJson("/api/v1/condominium-notices/{$aviso}", ['message' => 'Dia 6.'])->assertOk();
        Event::assertDispatched(
            CondominiumNoticeBroadcast::class,
            fn (CondominiumNoticeBroadcast $event) => $event->broadcastWith()['updated'] === true
        );
    }
}