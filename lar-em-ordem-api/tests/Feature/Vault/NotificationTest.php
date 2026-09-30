<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestProperty(): int
    {
        Schema::disableForeignKeyConstraints();

        $typeId = DB::table('property_types')->insertGetId([
            'type' => 'Apartamento',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $typologyId = DB::table('property_typologies')->insertGetId([
            'typology' => 'T2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $addressId = DB::table('addresses')->insertGetId([
            'street' => 'Rua Teste',
            'postal_code' => '4000-000',
            'door' => '1A',
            'county' => 'Porto',
            'location' => 'Porto',
            'district' => 'Porto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $propertyId = DB::table('properties')->insertGetId([
            'property_type_id' => $typeId,
            'property_typology_id' => $typologyId,
            'address_id' => $addressId,
            'area' => 100,
            'fraction' => 'A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::enableForeignKeyConstraints();

        return $propertyId;
    }

    public function test_user_can_list_notifications(): void
    {
        $user = User::factory()->create();
        $propertyId = $this->createTestProperty();

        DB::table('vault_notifications')->insert([
            'property_id' => $propertyId,
            'type' => 'Alerta',
            'title' => 'Documento a expirar',
            'message' => 'O seu certificado energético expira em 30 dias.',
            'alert_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/notifications?property_id={$propertyId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'property_id',
                        'type',
                        'title',
                        'message',
                        'alert_date',
                        'read_at'
                    ]
                ]
            ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $propertyId = $this->createTestProperty();

        $notificationId = DB::table('vault_notifications')->insertGetId([
            'property_id' => $propertyId,
            'type' => 'Alerta',
            'title' => 'Documento a expirar',
            'message' => 'O seu certificado energético expira em 30 dias.',
            'alert_date' => now(),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notificationId}/read");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('vault_notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }
}
