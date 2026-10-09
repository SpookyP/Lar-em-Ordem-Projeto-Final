<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Property\Property;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\Property\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestProperty()
    {
        $type = new PropertyType();
        $type->type = 'Apartamento';
        $type->save();

        $typology = new PropertyTypology();
        $typology->typology = 'T2';
        $typology->save();

        $address = new Address();
        $address->street = 'Rua Teste';
        $address->postal_code = '4000-000';
        $address->door = '1A';
        $address->county = 'Porto';
        $address->location = 'Porto';
        $address->district = 'Porto';
        $address->save();

        $property = new Property();
        $property->property_type_id = $type->id;
        $property->property_typology_id = $typology->id;
        $property->address_id = $address->id;
        $property->area = 100;
        $property->fraction = 'A';
        $property->save();

        return $property->id;
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
