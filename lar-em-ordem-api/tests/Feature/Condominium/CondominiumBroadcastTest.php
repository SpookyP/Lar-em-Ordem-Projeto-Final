<?php

namespace Tests\Feature\Condominium;

use App\Events\Condominium\CondominiumNoticeBroadcast;
use App\Models\Condominium\Condominium;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Real-time condominium notices (Reverb): a private channel per property and an event on publish/edit.
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

        // CondominiumSeeder: the resident's Matosinhos property belongs to "Edifício Brito Capelo"
        $this->morador    = User::where('email', 'morador@example.com')->firstOrFail();
        $this->admin      = User::where('email', 'condominio@example.com')->firstOrFail();
        $condominio       = Condominium::where('name', 'Edifício Brito Capelo')->firstOrFail();
        $this->condominio = $condominio->id;
        $this->casa       = $condominio->properties()->value('id');
    }

    /** Asks for authorisation to a property's private channel, the way Echo does. */
    private function autorizarCanal(User $user, string $propertyId)
    {
        // the null driver doesn't sign the authorisation: switch to reverb and register the channels on it again
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

        // no changes: nothing
        $this->actingAs($this->admin)->patchJson("/api/v1/condominium-notices/{$aviso}", ['title' => 'Corte de água'])->assertOk();
        Event::assertNotDispatched(CondominiumNoticeBroadcast::class);

        // with changes: event with updated = true
        $this->actingAs($this->admin)->patchJson("/api/v1/condominium-notices/{$aviso}", ['message' => 'Dia 6.'])->assertOk();
        Event::assertDispatched(
            CondominiumNoticeBroadcast::class,
            fn (CondominiumNoticeBroadcast $event) => $event->broadcastWith()['updated'] === true
        );
    }
}