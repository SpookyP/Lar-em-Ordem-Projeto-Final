<?php

namespace Tests\Feature\ServiceProvider;

use App\Models\File\File;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pedidos de assistência (módulo 5): listagem por casa, criação e edição com imagens,
 * detalhe, cancelamento e pedidos disponíveis para os prestadores.
 * Usa os dados do AssistanceRequestSeeder (casa do morador em Matosinhos, um pedido em cada estado).
 */
class AssistanceRequestTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $morador;
    private User $servicos;
    private User $servicinhos;
    private string $casa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('r2');

        $this->morador     = User::where('email', 'morador@example.com')->firstOrFail();
        $this->servicos    = User::where('email', 'service@example.com')->firstOrFail();
        $this->servicinhos = User::where('email', 'residente@example.com')->firstOrFail();
        $this->casa        = $this->pedido('Sanita entupida')->property_id;
    }

    // ---------------------------------------------------------------- helpers

    private function pedido(string $titulo): AssistanceRequest
    {
        return AssistanceRequest::where('title', $titulo)->firstOrFail();
    }

    private function dadosNovoPedido(array $extra = []): array
    {
        return $extra + [
            'property_id'         => $this->casa,
            'problem_category_id' => ProblemCategory::where('name', 'Canalização')->value('id'),
            'title'               => 'Torneira a pingar',
            'description'         => 'A torneira da cozinha não fecha bem.',
        ];
    }

    /** Pedido multipart (com ficheiros), como o frontend faz com FormData. */
    private function multipart(User $user, string $url, array $data)
    {
        return $this->actingAs($user)->post($url, $data, ['Accept' => 'application/json']);
    }

    /**
     * Ficheiro de imagem falso. Usa create() com o tipo MIME em vez de image(),
     * que precisa da extensão GD com suporte JPEG (não existe no container).
     */
    private function imagem(string $nome = 'foto.jpg'): UploadedFile
    {
        $mime = str_ends_with($nome, '.png') ? 'image/png' : 'image/jpeg';

        return UploadedFile::fake()->create($nome, 100, $mime);
    }

    // ------------------------------------------------------------------ index

    public function test_index_exige_autenticacao(): void
    {
        $this->getJson("/api/v1/assistance-requests?property_id={$this->casa}")->assertUnauthorized();
    }

    public function test_index_exige_a_casa(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/assistance-requests')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'Indique a casa.']);

        $this->actingAs($this->morador)
            ->getJson('/api/v1/assistance-requests?property_id=01aaaaaaaaaaaaaaaaaaaaaaaa')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'A casa indicada não existe.']);
    }

    public function test_index_devolve_so_os_pedidos_do_utilizador_nessa_casa(): void
    {
        // pedido do morador noutra casa
        $outraCasa = Property::factory()->create();
        PropertyContract::factory()->create([
            'resident_id' => $this->morador->resident->id,
            'property_id' => $outraCasa->id,
            'is_active'   => true,
        ]);
        AssistanceRequest::factory()->create([
            'user_id'             => $this->morador->id,
            'property_id'         => $outraCasa->id,
            'problem_category_id' => 1,
        ]);

        $resposta = $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}&per_page=15")
            ->assertOk()
            ->assertJsonCount(5, 'data');

        foreach ($resposta->json('data') as $pedido) {
            $this->assertSame($this->casa, $pedido['property']['id']);
        }

        // outro utilizador na mesma casa não vê os pedidos do morador
        $this->actingAs($this->servicos)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_filtra_por_estado_e_traz_a_contagem_de_propostas_pendentes(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}&status=open")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $contagens = collect($resposta->json('data'))->pluck('pending_proposals_count', 'title');

        $this->assertSame(2, $contagens['Tomada da sala sem corrente']);
        $this->assertSame(0, $contagens['Fuga de água debaixo do lava-loiça']);

        $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}&status=xpto")
            ->assertStatus(422);
    }

    public function test_index_e_paginado_e_os_links_mantem_os_filtros(): void
    {
        $resposta = $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}&per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);

        $next = $resposta->json('links.next');
        $this->assertStringContainsString("property_id={$this->casa}", $next);
        $this->assertStringContainsString('per_page=2', $next);

        // por defeito 5 por página
        $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}")
            ->assertJsonPath('meta.per_page', 5);

        $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests?property_id={$this->casa}&per_page=16")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['per_page' => 'O número de resultados por página tem de estar entre 1 e 15.']);
    }

    // ------------------------------------------------------------------ store

    public function test_o_morador_abre_um_pedido_sem_imagens(): void
    {
        $this->actingAs($this->morador)
            ->postJson('/api/v1/assistance-requests', $this->dadosNovoPedido())
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.title', 'Torneira a pingar')
            ->assertJsonCount(0, 'data.attachments');

        $this->assertDatabaseHas('assistance_requests', [
            'title'   => 'Torneira a pingar',
            'user_id' => $this->morador->id,
            'status'  => 'open',
        ]);
    }

    public function test_o_morador_abre_um_pedido_com_duas_imagens(): void
    {
        $this->multipart($this->morador, '/api/v1/assistance-requests', $this->dadosNovoPedido([
            'images' => [$this->imagem('a.jpg'), $this->imagem('b.png')],
        ]))
            ->assertCreated()
            ->assertJsonCount(2, 'data.attachments')
            ->assertJsonStructure(['data' => ['attachments' => [['id', 'original_name', 'url']]]]);

        $this->assertCount(2, Storage::disk('r2')->allFiles());
    }

    public function test_nao_e_possivel_enviar_mais_de_duas_imagens(): void
    {
        $this->multipart($this->morador, '/api/v1/assistance-requests', $this->dadosNovoPedido([
            'images' => [$this->imagem(), $this->imagem(), $this->imagem()],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images' => 'Cada pedido pode ter no máximo 2 imagens.']);

        $this->assertDatabaseMissing('assistance_requests', ['title' => 'Torneira a pingar']);
    }

    public function test_os_campos_sensiveis_nao_vem_do_body(): void
    {
        $this->actingAs($this->morador)
            ->postJson('/api/v1/assistance-requests', $this->dadosNovoPedido([
                'user_id' => $this->servicos->id,
                'status'  => 'closed',
            ]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('assistance_requests', [
            'title'   => 'Torneira a pingar',
            'user_id' => $this->morador->id,
        ]);
    }

    public function test_so_e_possivel_abrir_pedidos_em_casas_com_contrato_ativo(): void
    {
        $casaDeOutro = Property::factory()->create();

        $this->actingAs($this->morador)
            ->postJson('/api/v1/assistance-requests', $this->dadosNovoPedido(['property_id' => $casaDeOutro->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['property_id' => 'Só pode abrir pedidos para casas onde tem um contrato ativo.']);
    }

    public function test_so_residentes_abrem_pedidos(): void
    {
        $semPerfil = User::factory()->create();

        $this->actingAs($semPerfil)
            ->postJson('/api/v1/assistance-requests', $this->dadosNovoPedido())
            ->assertForbidden();
    }

    // ------------------------------------------------------------------- show

    public function test_o_dono_ve_o_pedido_com_todas_as_propostas_e_a_morada(): void
    {
        $tomada = $this->pedido('Tomada da sala sem corrente');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/assistance-requests/{$tomada->id}")
            ->assertOk()
            ->assertJsonCount(3, 'data.proposals')
            ->assertJsonPath('data.property.id', $tomada->property_id)
            ->assertJsonPath('data.property.street', 'Rua Brito Capelo')
            ->assertJsonStructure(['data' => ['proposals' => [['id', 'status', 'service_provider' => ['company_name']]]]]);
    }

    public function test_o_prestador_com_proposta_ve_o_pedido_sem_propostas_nem_morada(): void
    {
        $tomada = $this->pedido('Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->getJson("/api/v1/assistance-requests/{$tomada->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.proposals')
            ->assertJsonPath('data.property.id', null)
            ->assertJsonPath('data.property.street', null)
            ->assertJsonPath('data.property.county', 'Matosinhos');
    }

    public function test_o_prestador_com_a_proposta_aceite_ve_a_morada(): void
    {
        $sanita = $this->pedido('Sanita entupida');

        $this->actingAs($this->servicos)
            ->getJson("/api/v1/assistance-requests/{$sanita->id}")
            ->assertOk()
            ->assertJsonPath('data.property.street', 'Rua Brito Capelo')
            ->assertJsonPath('data.accepted_proposal.status', 'accepted');
    }

    public function test_quem_nao_tem_relacao_com_o_pedido_recebe_403(): void
    {
        $estranho = User::factory()->create();
        $estranho->assignRole('resident');

        $this->actingAs($estranho)
            ->getJson('/api/v1/assistance-requests/' . $this->pedido('Tomada da sala sem corrente')->id)
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- update

    public function test_o_dono_edita_titulo_e_descricao(): void
    {
        $fuga = $this->pedido('Fuga de água debaixo do lava-loiça');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/assistance-requests/{$fuga->id}", ['title' => 'Fuga grande'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Fuga grande')
            ->assertJsonPath('data.description', $fuga->description);
    }

    public function test_o_dono_troca_uma_imagem_no_mesmo_pedido(): void
    {
        $criado = $this->multipart($this->morador, '/api/v1/assistance-requests', $this->dadosNovoPedido([
            'images' => [$this->imagem('a.jpg'), $this->imagem('b.png')],
        ]))->assertCreated();

        $id      = $criado->json('data.id');
        $remover = $criado->json('data.attachments.0.id');

        // com imagens: multipart em POST + _method=PATCH
        $resposta = $this->multipart($this->morador, "/api/v1/assistance-requests/{$id}", [
            '_method'               => 'PATCH',
            'images'                => [$this->imagem('c.jpg')],
            'remove_attachment_ids' => [$remover],
        ])->assertOk()->assertJsonCount(2, 'data.attachments');

        $nomes = collect($resposta->json('data.attachments'))->pluck('original_name')->sort()->values()->all();
        $this->assertSame(['b.png', 'c.jpg'], $nomes);
        $this->assertCount(2, Storage::disk('r2')->allFiles());
    }

    public function test_juntar_uma_terceira_imagem_falha_e_nao_altera_nada(): void
    {
        $criado = $this->multipart($this->morador, '/api/v1/assistance-requests', $this->dadosNovoPedido([
            'images' => [$this->imagem(), $this->imagem()],
        ]))->assertCreated();

        $id = $criado->json('data.id');

        $this->multipart($this->morador, "/api/v1/assistance-requests/{$id}", [
            '_method' => 'PATCH',
            'title'   => 'Não deve gravar',
            'images'  => [$this->imagem()],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images' => 'Cada pedido pode ter no máximo 2 imagens.']);

        $this->assertSame('Torneira a pingar', AssistanceRequest::find($id)->title);
        $this->assertCount(2, Storage::disk('r2')->allFiles());
    }

    public function test_nao_e_possivel_remover_imagens_de_outro_pedido(): void
    {
        $outro = $this->multipart($this->morador, '/api/v1/assistance-requests', $this->dadosNovoPedido([
            'images' => [$this->imagem()],
        ]))->assertCreated();

        $fuga      = $this->pedido('Fuga de água debaixo do lava-loiça');
        $ficheiros = File::count();

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/assistance-requests/{$fuga->id}", [
                'remove_attachment_ids' => [$outro->json('data.attachments.0.id')],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['remove_attachment_ids' => 'Uma ou mais imagens indicadas não pertencem a este pedido.']);

        $this->assertSame($ficheiros, File::count());
    }

    public function test_so_e_possivel_editar_pedidos_abertos(): void
    {
        $this->actingAs($this->morador)
            ->patchJson('/api/v1/assistance-requests/' . $this->pedido('Sanita entupida')->id, ['title' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível editar pedidos abertos.']);
    }

    public function test_so_o_dono_edita_o_pedido(): void
    {
        $this->actingAs($this->servicos)
            ->patchJson('/api/v1/assistance-requests/' . $this->pedido('Tomada da sala sem corrente')->id, ['title' => 'x'])
            ->assertForbidden();
    }

    public function test_nao_existe_rota_para_apagar_pedidos(): void
    {
        $this->actingAs($this->morador)
            ->deleteJson('/api/v1/assistance-requests/' . $this->pedido('Fuga de água debaixo do lava-loiça')->id)
            ->assertStatus(405);
    }

    // ----------------------------------------------------------------- cancel

    public function test_o_dono_cancela_um_pedido_aberto_e_as_propostas_pendentes_sao_rejeitadas(): void
    {
        $tomada = $this->pedido('Tomada da sala sem corrente');

        $this->actingAs($this->morador)
            ->patchJson("/api/v1/assistance-requests/{$tomada->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $tomada->refresh();
        $this->assertNotNull($tomada->closed_at);
        $this->assertSame(0, $tomada->proposals()->where('status', 'pending')->count());
        $this->assertSame(3, $tomada->proposals()->where('status', 'rejected')->count());
    }

    public function test_so_e_possivel_cancelar_pedidos_abertos(): void
    {
        $this->actingAs($this->morador)
            ->patchJson('/api/v1/assistance-requests/' . $this->pedido('Sanita entupida')->id . '/cancel')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Só é possível cancelar pedidos abertos.']);
    }

    public function test_so_o_dono_cancela_o_pedido(): void
    {
        $tomada = $this->pedido('Tomada da sala sem corrente');

        $this->actingAs($this->servicos)
            ->patchJson("/api/v1/assistance-requests/{$tomada->id}/cancel")
            ->assertForbidden();

        $this->assertSame('open', $tomada->fresh()->status);
    }

    // -------------------------------------------------------------- available

    public function test_available_mostra_os_pedidos_da_categoria_e_zona_do_prestador(): void
    {
        // "Serviços Lda": canalização + eletricidade, Matosinhos + Porto.
        // A "Tomada" fica de fora porque já enviou proposta; fechados/cancelados nunca aparecem.
        $resposta = $this->actingAs($this->servicos)
            ->getJson('/api/v1/assistance-requests/available')
            ->assertOk();

        $titulos = collect($resposta->json('data'))->pluck('title')->all();

        $this->assertSame(['Fuga de água debaixo do lava-loiça'], $titulos);
        $this->assertNull($resposta->json('data.0.property.street'));
        $this->assertNull($resposta->json('data.0.property.id'));
    }

    public function test_available_ignora_pedidos_de_outra_categoria(): void
    {
        // "Serviçinhos Lda": só eletricidade; a "Fuga" é de canalização e a "Tomada" já tem a sua proposta
        $this->actingAs($this->servicinhos)
            ->getJson('/api/v1/assistance-requests/available')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_available_ignora_pedidos_fora_das_zonas_do_prestador(): void
    {
        $this->servicos->service_provider->zones()->sync([]);

        $this->actingAs($this->servicos)
            ->getJson('/api/v1/assistance-requests/available')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_available_e_paginado(): void
    {
        $this->actingAs($this->servicos)
            ->getJson('/api/v1/assistance-requests/available?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_so_prestadores_veem_os_pedidos_disponiveis(): void
    {
        $this->actingAs($this->morador)
            ->getJson('/api/v1/assistance-requests/available')
            ->assertForbidden();
    }
}