<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * Propostas (módulo 5): o prestador envia, edita e retira; o residente aceita ou rejeita.
 * Usa os dados do AssistanceRequestSeeder:
 * - "Fuga de água" aberta e sem propostas;
 * - "Tomada da sala" aberta, com propostas pendentes do "Serviços Lda" e do "Serviçinhos Lda" e uma rejeitada;
 * - "Sanita entupida" fechada, com a proposta do "Serviços Lda" aceite.
 */
class ProposalTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $morador;
    private User $servicos;
    private User $servicinhos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->morador     = User::where('email', 'morador@example.com')->firstOrFail();
        $this->servicos    = User::where('email', 'service@example.com')->firstOrFail();
        $this->servicinhos = User::where('email', 'residente@example.com')->firstOrFail();
    }

    // ---------------------------------------------------------------- helpers

    private function pedido(string $titulo): AssistanceRequest
    {
        return AssistanceRequest::where('title', $titulo)->firstOrFail();
    }

    /** Proposta de um prestador num pedido (pelo título do pedido). */
    private function proposta(User $prestador, string $titulo): Proposal
    {
        return $this->pedido($titulo)->proposals()
            ->where('service_provider_id', $prestador->service_provider->id)
            ->firstOrFail();
    }

    private function dadosProposta(array $extra = []): array
    {
        return $extra + [
            'estimated_value' => 80,
            'description'     => 'Substituição do sifão e vedação.',
            'validity'        => now()->addDays(10)->toDateString(),
            'available_date'  => now()->addDays(2)->toDateString(),
            'available_from'  => '09:00',
            'available_until' => '12:00',
        ];
    }

    // ------------------------------------------------------------------ store

    public function test_o_prestador_envia_uma_proposta_a_um_pedido_disponivel(): void
    {
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->servicos)
            ->postJson("/api/v1/assistance-requests/{$fuga->id}/proposals", $this->dadosProposta())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.available_from', '09:00')
            ->assertJsonPath('data.service_provider.company_name', 'Serviços Lda');

        $this->assertDatabaseHas('proposals', [
            'assistance_request_id' => $fuga->id,
            'service_provider_id'   => $this->servicos->service_provider->id,
            'status'                => 'pending',
        ]);
    }

    public function test_os_campos_sensiveis_nao_vem_do_body(): void
    {
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->servicos)
            ->postJson("/api/v1/assistance-requests/{$fuga->id}/proposals", $this->dadosProposta([
                'status'              => 'accepted',
                'service_provider_id' => $this->servicinhos->service_provider->id,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.service_provider.company_name', 'Serviços Lda');
    }

    public function test_cada_prestador_so_envia_uma_proposta_por_pedido(): void
    {
        $tomada = $this->pedido('Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->postJson("/api/v1/assistance-requests/{$tomada->id}/proposals", $this->dadosProposta())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assistance_request' => 'Já enviou uma proposta para este pedido.']);
    }

    public function test_nao_e_possivel_propor_a_um_pedido_que_nao_esta_disponivel(): void
    {
        // a "Fuga" é de canalização e o "Serviçinhos Lda" só faz eletricidade
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->servicinhos)
            ->postJson("/api/v1/assistance-requests/{$fuga->id}/proposals", $this->dadosProposta())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assistance_request' => 'Este pedido não está disponível para receber a sua proposta.']);
    }

    public function test_a_proposta_valida_datas_e_horas(): void
    {
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->servicos)
            ->postJson("/api/v1/assistance-requests/{$fuga->id}/proposals", $this->dadosProposta([
                'validity'        => now()->subDay()->toDateString(),
                'available_from'  => '14:00',
                'available_until' => '10:00',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'validity'        => 'A validade da proposta não pode ser uma data passada.',
                'available_until' => 'A hora de fim tem de ser depois da hora de início.',
            ]);
    }

    public function test_so_prestadores_enviam_propostas(): void
    {
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->morador)
            ->postJson("/api/v1/assistance-requests/{$fuga->id}/proposals", $this->dadosProposta())
            ->assertForbidden();
    }

    // ------------------------------------------------------------------- mine

    public function test_o_prestador_ve_as_suas_propostas_com_o_pedido(): void
    {
        // "Serviços Lda": Tomada (pendente), Sanita (aceite), Esquentador (aceite)
        $this->actingAs($this->servicos)
            ->getJson('/api/v1/proposals/mine')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'status', 'assistance_request' => ['id', 'title', 'status']]]]);
    }

    public function test_as_propostas_do_prestador_sao_paginadas(): void
    {
        $this->actingAs($this->servicos)
            ->getJson('/api/v1/proposals/mine?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($this->servicos)
            ->getJson('/api/v1/proposals/mine?per_page=0')
            ->assertStatus(422);
    }

    public function test_so_prestadores_veem_as_suas_propostas(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/proposals/mine')
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- update

    public function test_o_prestador_edita_uma_proposta_pendente(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$proposta->id}", ['estimated_value' => 50])
            ->assertOk()
            ->assertJsonPath('data.estimated_value', '50.00')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_a_hora_de_fim_e_comparada_com_a_hora_de_inicio_guardada(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');
        $inicio   = substr($proposta->available_from, 0, 5);

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$proposta->id}", ['available_until' => $inicio])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['available_until' => 'A hora de fim tem de ser depois da hora de início.']);
    }

    public function test_so_e_possivel_editar_propostas_pendentes(): void
    {
        $aceite = $this->proposta($this->servicos, 'Sanita entupida');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$aceite->id}", ['estimated_value' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível editar propostas pendentes.']);
    }

    public function test_so_o_prestador_dono_edita_a_proposta(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->servicinhos)
            ->patchJson("/api/v1/proposals/{$proposta->id}", ['estimated_value' => 1])
            ->assertForbidden();
    }

    // --------------------------------------------------------------- withdraw

    public function test_o_prestador_retira_uma_proposta_pendente_e_o_pedido_continua_aberto(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$proposta->id}/withdraw")
            ->assertOk()
            ->assertJsonPath('data.status', 'withdrawn');

        $this->assertSame('open', $this->pedido('Tomada da sala sem corrente')->status);
    }

    public function test_so_e_possivel_retirar_propostas_pendentes(): void
    {
        $aceite = $this->proposta($this->servicos, 'Sanita entupida');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$aceite->id}/withdraw")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível retirar propostas pendentes.']);
    }

    public function test_o_residente_nao_retira_propostas(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$proposta->id}/withdraw")
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- accept

    public function test_o_residente_aceita_uma_proposta_e_o_pedido_fecha(): void
    {
        $aceite = $this->proposta($this->servicos, 'Tomada da sala sem corrente');
        $outra  = $this->proposta($this->servicinhos, 'Tomada da sala sem corrente');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$aceite->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertNotNull($aceite->fresh()->response_date);
        $this->assertSame('rejected', $outra->fresh()->status);

        $tomada = $this->pedido('Tomada da sala sem corrente');
        $this->assertSame('closed', $tomada->status);
        $this->assertNotNull($tomada->closed_at);

        // o prestador aceite passa a ver a morada
        $this->actingAs($this->servicos)
            ->getJson("/api/v1/assistance-requests/{$tomada->id}")
            ->assertJsonPath('data.property.street', 'Rua Brito Capelo');
    }

    public function test_so_e_possivel_aceitar_propostas_pendentes(): void
    {
        $rejeitada = $this->pedido('Tomada da sala sem corrente')->proposals()
            ->where('status', 'rejected')
            ->firstOrFail();

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$rejeitada->id}/accept")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível aceitar propostas pendentes.']);
    }

    public function test_nao_e_possivel_aceitar_uma_proposta_de_um_pedido_fechado(): void
    {
        $pendente = Proposal::factory()->create([
            'assistance_request_id' => $this->pedido('Sanita entupida')->id,
            'service_provider_id'   => $this->servicinhos->service_provider->id,
        ]);

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$pendente->id}/accept")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'O pedido já não está aberto.']);
    }

    public function test_nao_e_possivel_aceitar_uma_proposta_expirada(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');
        $proposta->update(['validity' => now()->subDay()->toDateString()]);

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$proposta->id}/accept")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['validity' => 'Esta proposta já expirou.']);

        $this->assertSame('open', $this->pedido('Tomada da sala sem corrente')->status);
    }

    public function test_so_o_dono_do_pedido_aceita_propostas(): void
    {
        $proposta = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$proposta->id}/accept")
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- reject

    public function test_o_residente_rejeita_uma_proposta_e_o_pedido_continua_aberto(): void
    {
        $rejeitada = $this->proposta($this->servicinhos, 'Tomada da sala sem corrente');
        $outra     = $this->proposta($this->servicos, 'Tomada da sala sem corrente');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$rejeitada->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertNotNull($rejeitada->fresh()->response_date);
        $this->assertSame('pending', $outra->fresh()->status);
        $this->assertSame('open', $this->pedido('Tomada da sala sem corrente')->status);
    }

    public function test_so_e_possivel_rejeitar_propostas_pendentes(): void
    {
        $aceite = $this->proposta($this->servicos, 'Sanita entupida');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/proposals/{$aceite->id}/reject")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível rejeitar propostas pendentes.']);
    }

    public function test_so_o_dono_do_pedido_rejeita_propostas(): void
    {
        $proposta = $this->proposta($this->servicinhos, 'Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/proposals/{$proposta->id}/reject")
            ->assertForbidden();
    }
}