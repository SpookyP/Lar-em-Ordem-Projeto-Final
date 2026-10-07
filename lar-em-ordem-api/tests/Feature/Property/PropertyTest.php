<?php

namespace Tests\Feature\Property;

use App\Models\Property\PropertyContract;//Contract
use App\Models\Property\Property;
use App\Models\User\Resident;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Helper to create and authenticate a user with a Resident profile and role.
     */
    protected function createResidentUser(): User
    {
        $user = User::factory()->create();$user->assignRole('resident');
        Resident::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Helper to create a property linked to a user via their Resident profile and Contract.
     */
    protected function createPropertyForUser(User $user, array$propertyAttributes = []): Property
    {
        $resident = $user->resident ?? Resident::factory()->create(['user_id' => $user->id]);

        $property = Property::factory()->create($propertyAttributes);

        PropertyContract::factory()->create([
            'resident_id' => $resident->id,
            'property_id' => $property->id,
        ]);

        return $property;
    }

    /*
    |--------------------------------------------------------------------------
    | 1. AUTHORIZATION CHECKS (401 & 403)
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_properties_api(): void
    {
        $this->getJson('/api/v1/properties/forms-options')
            ->assertStatus(401);

        $this->postJson('/api/v1/properties', [])
            ->assertStatus(401);
    }

    public function test_user_without_resident_profile_cannot_create_property(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/properties', [
                'property_type_id'     => 1,
                'property_typology_id' => 1,
                'resident_type_id'     => 1,
                'start_date'           => '2026-10-01',
                'address'              => [
                    'street'      => 'Rua Teste',
                    'postal_code' => '4000-000',
                    'door'        => '1',
                    'county'      => 'Porto',
                    'location'    => 'Porto',
                    'district'    => 'Porto',
                ],
            ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. FORM OPTIONS
    |--------------------------------------------------------------------------
    */

    public function test_resident_user_can_get_property_form_options(): void
    {
        $user = $this->createResidentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/properties/forms-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'propertyTypes' => [
                        '*' => ['id', 'type'],
                    ],
                    'propertyTypologies' => [
                        '*' => ['id', 'typology'],
                    ],
                    'residentTypes' => [
                        '*' => ['id', 'type'],
                    ],
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. CREATION (HAPPY PATH)
    |--------------------------------------------------------------------------
    */

    public function test_resident_user_can_create_property_with_address(): void
    {
        $user = $this->createResidentUser();

        $payload = [
            'property_type_id'     => 1,
            'property_typology_id' => 1,
            'condominium_id'       => null,
            'area'                 => 120,
            'fraction'             => '2º Dto',
            'resident_type_id'     => 1,
            'start_date'           => '2026-10-01',
            'end_date'             => null,
            'address'              => [
                'street'      => 'Rua Principal',
                'postal_code' => '4000-000',
                'door'        => '100',
                'county'      => 'Matosinhos',
                'location'    => 'Senhora da Hora',
                'district'    => 'Porto',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/properties', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('properties', [
            'property_type_id'     => 1,
            'property_typology_id' => 1,
            'fraction'             => '2º Dto',
            'area'                 => 120,
        ]);

        $this->assertDatabaseHas('addresses', [
            'street'      => 'Rua Principal',
            'postal_code' => '4000-000',
            'door'        => '100',
            'county'      => 'Matosinhos',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. MISSING & INVALID INPUTS
    |--------------------------------------------------------------------------
    */

    public function test_fails_when_required_property_fields_are_missing(): void
    {
        $user = $this->createResidentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/properties', [
                'area' => 120,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'property_type_id',
                'property_typology_id',
                'resident_type_id',
                'start_date',
                'address',
            ]);
    }

    public function test_fails_when_nested_address_fields_are_missing(): void
    {
        $user = $this->createResidentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/properties', [
                'property_type_id'     => 1,
                'property_typology_id' => 1,
                'resident_type_id'     => 1,
                'start_date'           => '2026-10-01',
                'area'                 => 120,
                'fraction'             => '2º Dto',
                'address'              => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'address.county',
                'address.location',
                'address.district',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. DATA ISOLATION & AUTHORIZATION (VIEWING)
    |--------------------------------------------------------------------------
    */

    public function test_user_can_only_list_their_own_properties(): void
    {
        $userA = $this->createResidentUser();
        $userB = $this->createResidentUser();

        $propertyA = $this->createPropertyForUser($userA);
        $propertyB = $this->createPropertyForUser($userB);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/properties');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $propertyA->id])
            ->assertJsonMissing(['id' => $propertyB->id]);
    }

    public function test_user_cannot_view_another_users_property_details(): void
    {
        $userA = $this->createResidentUser();
        $userB = $this->createResidentUser();

        $propertyB = $this->createPropertyForUser($userB);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/v1/properties/{$propertyB->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    /*
    |--------------------------------------------------------------------------
    | 6. MUTATION SECURITY (EDITING & DELETING)
    |--------------------------------------------------------------------------
    */

    public function test_user_can_update_their_own_property(): void
    {
        $user = $this->createResidentUser();
        $property = $this->createPropertyForUser($user, ['area' => 100]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/properties/{$property->id}", [
                'area' => 150,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('properties', [
            'id'   => $property->id,
            'area' => 150,
        ]);
    }

    public function test_user_cannot_update_another_users_property(): void
    {
        $userA = $this->createResidentUser();
        $userB = $this->createResidentUser();

        $propertyB = $this->createPropertyForUser($userB, ['area' => 100]);

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/v1/properties/{$propertyB->id}", [
                'area' => 999,
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('properties', [
            'id'   => $propertyB->id,
            'area' => 100,
        ]);
    }

    public function test_user_cannot_delete_another_users_property(): void
    {
        $userA = $this->createResidentUser();
        $userB = $this->createResidentUser();

        $propertyB = $this->createPropertyForUser($userB);

        $response = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/v1/properties/{$propertyB->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));

        $this->assertDatabaseHas('properties', [
            'id' => $propertyB->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. EDGE CASES & NON-EXISTENT RESOURCES
    |--------------------------------------------------------------------------
    */

    public function test_returns_404_when_property_does_not_exist(): void
    {
        $user = $this->createResidentUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/properties/999999')
            ->assertStatus(404);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/properties/999999', ['area' => 100])
            ->assertStatus(404);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/properties/999999')
            ->assertStatus(404);
    }

    public function test_fails_when_end_date_is_before_start_date(): void
    {
        $user = $this->createResidentUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/properties', [
                'property_type_id'     => 1,
                'property_typology_id' => 1,
                'resident_type_id'     => 1,
                'start_date'           => '2026-10-01',
                'end_date'             => '2026-09-01',
                'area'                 => 100,
                'fraction'             => '1º A',
                'address'              => [
                    'street'      => 'Rua Teste',
                    'postal_code' => '4000-000',
                    'door'        => '1',
                    'county'      => 'Porto',
                    'location'    => 'Porto',
                    'district'    => 'Porto',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }
}