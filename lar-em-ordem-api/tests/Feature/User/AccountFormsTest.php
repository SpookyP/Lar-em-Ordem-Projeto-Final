<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Errors the sign-up and profile forms show must point at the real problem.
 */
class AccountFormsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Http::fake(); // the "uncompromised" password rule would call an external service
    }

    private function signUpAsResident(string $email, string $password): array
    {
        return [
            'name' => 'Conta Existente',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'resident',
            'nif' => '212345678',
        ];
    }

    public function test_a_taken_email_says_so_instead_of_complaining_about_the_password(): void
    {
        $this->postJson('/api/auth/register', $this->signUpAsResident('service@example.com', 'fraca'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email' => 'O email já está em uso, tente novamente.'])
            ->assertJsonMissingValidationErrors(['password']);
    }

    public function test_a_new_email_still_gets_the_password_rules(): void
    {
        $this->postJson('/api/auth/register', $this->signUpAsResident('nova.conta@example.com', 'fraca'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonMissingValidationErrors(['email']);
    }

    public function test_the_existing_password_adds_the_profile_even_if_it_predates_the_strength_rules(): void
    {
        // the seeded password ("password") would not pass the rules for new passwords;
        // signing in at the end needs the SPA session
        $this->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->postJson('/api/auth/register', $this->signUpAsResident('service@example.com', 'password'))
            ->assertCreated();

        $this->assertTrue(User::where('email', 'service@example.com')->firstOrFail()->hasRole('resident'));
    }

    public function test_a_request_without_email_or_password_is_a_validation_error(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Sem Dados', 'role' => 'resident', 'nif' => '212345678'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_editing_the_profile_without_a_role_is_a_validation_error(): void
    {
        $this->actingAs(User::where('email', 'morador@example.com')->firstOrFail())
            ->putJson('/user/profile-information', ['name' => 'Sem Perfil'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }
}