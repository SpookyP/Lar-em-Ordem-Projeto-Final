<?php

namespace Tests\Feature\Vault;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\Resident;
use App\Models\User\ResidentType;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUserAndProperty(): array
    {
        $this->seed([
            \Database\Seeders\PropertyTypeSeeder::class,
            \Database\Seeders\PropertyTypologySeeder::class,
            \Database\Seeders\ResidentTypeSeeder::class,
        ]);

        $propertyTypeId = PropertyType::first()->id;
        $propertyTypologyId = PropertyTypology::first()->id;
        $residentTypeId = ResidentType::first()->id;

        $user = User::factory()->create();
        $resident = Resident::factory()->create(['user_id' => $user->id]);

        $property = Property::factory()->create([
            'property_type_id' => $propertyTypeId,
            'property_typology_id' => $propertyTypologyId,
        ]);

        PropertyContract::factory()->create([
            'resident_id' => $resident->id,
            'property_id' => $property->id,
            'resident_type_id' => $residentTypeId,
            'is_active' => true,
        ]);

        return [$user, $property];
    }

    public function test_user_can_list_notifications(): void
    {
        [$user, $property] = $this->createAuthorizedUserAndProperty();

        DB::table('vault_notifications')->insert([
            'property_id' => $property->id,
            'type' => 'Alerta',
            'title' => 'Documento a expirar',
            'message' => 'O seu certificado energético expira em 30 dias.',
            'alert_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/properties/{$property->id}/notifications");

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
        [$user, $property] = $this->createAuthorizedUserAndProperty();

        $notificationId = DB::table('vault_notifications')->insertGetId([
            'property_id' => $property->id,
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
