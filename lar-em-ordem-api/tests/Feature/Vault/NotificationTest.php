<?php

namespace Tests\Feature\Vault;

use App\Models\User;
use App\Models\Vault\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    // Limpa e reconstrói a base de dados antes de cada teste correr, garantindo isolamento
    use RefreshDatabase;

    private int $propertyId;
    private User $user;

    /**
     * O método setUp corre automaticamente ANTES de cada teste individual.
     * Serve para preparar o estado base que todos os testes vão precisar.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cria um utilizador falso
        $this->user = User::factory()->create();

        $this->propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => 1,
            'property_typology_id' => 1,
            'address_id' => 1,
            'area' => 100,
            'fraction' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Testa se um utilizador consegue obter a lista das suas notificações.
     */
    public function test_user_can_list_notifications(): void
    {
        // Inserir uma notificação na base de dados
        Notification::create([
            'property_id' => $this->propertyId,
            'type' => 'document_expiry',
            'title' => 'Test Alert',
            'message' => 'This is a test notification.',
            'alert_date' => now(),
        ]);

        // Fazer um pedido GET autenticado à rota de listagem
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/notifications?property_id={$this->propertyId}");

        // Verificar se a API devolve 200 OK e a estrutura JSON correta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'property_id', 'title', 'message', 'is_read']
                ]
            ]);
    }

    /**
     * Testa se um utilizador consegue marcar uma notificação como lida.
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        // Criar uma notificação por ler
        $notification = Notification::create([
            'property_id' => $this->propertyId,
            'type' => 'system_alert',
            'title' => 'Unread Alert',
            'message' => 'Please read me.',
            'alert_date' => now(),
        ]);

        // Fazer um pedido PATCH autenticado para atualizar o estado
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read");

        // Confirmar a resposta da API
        $response->assertStatus(200)
            ->assertJsonPath('data.is_read', true);

        // Confirmar que a coluna read_at foi preenchida
        // O método fresh() vai buscar a versão mais recente do registo à base de dados
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
