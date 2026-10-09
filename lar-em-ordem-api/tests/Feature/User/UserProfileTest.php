<?php

namespace Tests\Feature\User;

use App\Models\User\Partner;
use App\Models\User\Resident;
use App\Models\User\ServiceProvider;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /*
    |--------------------------------------------------------------------------
    | 1. EVERY ROLE (HAPPY PATHS)
    |--------------------------------------------------------------------------
    */

    public function test_user_can_create_resident_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role' => 'resident',
                'nif'  => '212345678',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', "Profile 'resident' added successfully.");

        $this->assertDatabaseHas('residents', [
            'user_id' => $user->id,
            'name'    => $user->name,
            'nif'     => '212345678',
        ]);

        $this->assertTrue($user->fresh()->hasRole('resident'));
    }

    public function test_user_can_create_partner_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role'        => 'partner',
                'nif'         => '298765432',
                'phone'       => '912345678',
                'description' => 'Local hardware partner',
                'website'     => 'https://partner.example.com',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('partners', [
            'user_id' => $user->id,
            'nif'     => '298765432',
            'phone'   => '912345678',
        ]);

        $this->assertTrue($user->fresh()->hasRole('partner'));
    }

    public function test_user_can_create_service_provider_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role'           => 'service_provider',
                'company_name'   => 'Clean & Fix Lda',
                'nif'            => '501234567',
                'phone'          => '931234567',
                'provider_email' => 'contact@cleanandfix.pt',
                'description'    => 'Cleaning and plumbing services',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('service_providers', [
            'user_id'      => $user->id,
            'company_name' => 'Clean & Fix Lda',
            'email'        => 'contact@cleanandfix.pt',
        ]);

        $this->assertTrue($user->fresh()->hasRole('service_provider'));
    }

    /*
    |--------------------------------------------------------------------------
    | 2. INCORRECT & MISSING INPUTS
    |--------------------------------------------------------------------------
    */

    public function test_fails_when_unsupported_role_is_provided(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role' => 'admin', // Not allowed in Rule::in
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_fails_when_required_role_fields_are_missing(): void
    {
        $user = User::factory()->create();

        // Testing missing fields for service_provider
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role' => 'service_provider',
                // Missing: company_name, nif, phone, provider_email, description
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'company_name',
                'nif',
                'phone',
                'provider_email',
                'description',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. INVALID INPUT FORMATS
    |--------------------------------------------------------------------------
    */

    public function test_fails_for_invalid_nif_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role' => 'resident',
                'nif'  => '12345', // Must be exactly 9 digits
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nif']);
    }

    public function test_fails_for_invalid_email_and_website_formats(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role'        => 'partner',
                'nif'         => '212345678',
                'phone'       => '912345678',
                'description' => 'Test partner',
                'website'     => 'not-a-valid-url', // Must be a URL
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. DUPLICATED INPUTS
    |--------------------------------------------------------------------------
    */

    public function test_prevents_duplicate_resident_profile(): void
    {
        $user = User::factory()->create();
        Resident::factory()->create(['user_id' => $user->id]);
        $user->assignRole('resident');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role' => 'resident',
                'nif'  => '999888777',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_prevents_duplicate_provider_email_across_users(): void
    {
        // User 1 creates a service provider with a specific email
        $existingProvider = ServiceProvider::factory()->create([
            'email' => 'unique@provider.pt',
        ]);

        // User 2 attempts to register with the same provider email
        $user2 = User::factory()->create();

        $response = $this->actingAs($user2, 'sanctum')
            ->postJson('/api/user/profile', [
                'role'           => 'service_provider',
                'company_name'   => 'New Company',
                'nif'            => '509999999',
                'phone'          => '910000000',
                'provider_email' => 'unique@provider.pt', // Already taken
                'description'    => 'New description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider_email']);
    }
    /*
    |--------------------------------------------------------------------------
    | 5. AUTHENTICATION & EXTRA DUPLICATE CHECKS
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_create_profile(): void
    {
        // Act without actingAs()
        $response = $this->postJson('/api/user/profile', [
            'role' => 'resident',
            'nif'  => '212345678',
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    public function test_prevents_duplicate_partner_profile(): void
    {
        $user = User::factory()->create();
        Partner::factory()->create(['user_id' => $user->id]);
        $user->assignRole('partner');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/profile', [
                'role'        => 'partner',
                'nif'         => '298765432',
                'phone'       => '912345678',
                'description' => 'Second partner profile',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_authenticated_user_can_fetch_their_info_with_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('resident');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id'    => $user->id,
                'email' => $user->email,
            ])
            ->assertJsonFragment(['resident']);
    }
}
