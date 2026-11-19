<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use App\Notifications\ResetPassword;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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

    public function test_a_leaked_password_is_explained_in_portuguese(): void
    {
        // pretend the password shows up in the leaked-passwords list
        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier {
            public function verify($data)
            {
                return false;
            }
        });

        $this->postJson('/api/auth/register', $this->signUpAsResident('nova.conta@example.com', 'Lar-em-Ordem-2026!'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password' => 'Esta password apareceu numa fuga de dados. Por segurança, escolha outra.']);
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

    public function test_a_rule_without_its_own_message_answers_in_portuguese(): void
    {
        $data = ['name' => str_repeat('a', 256)] + $this->signUpAsResident('nova.conta@example.com', 'Lar-em-Ordem-2026!');

        $this->postJson('/api/auth/register', $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name' => 'O campo nome não pode ter mais de 255 caracteres.']);
    }

        public function test_a_name_with_a_link_is_refused(): void
    {
        $data = ['name' => 'Ana [clique aqui](https://evil.example)'] + $this->signUpAsResident('nova.conta@example.com', 'Lar-em-Ordem-2026!');

        $this->postJson('/api/auth/register', $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name' => 'O nome só pode ter letras, espaços, apóstrofos e hífenes.']);
    }

    public function test_forgot_password_answers_the_same_whether_the_account_exists_or_not(): void
    {
        Notification::fake();

        $sent = $this->postJson('/forgot-password', ['email' => 'morador@example.com'])->assertOk()->json();

        $this->postJson('/forgot-password', ['email' => 'ninguem@example.com'])->assertOk()->assertExactJson($sent);
        // a second link within the minute is refused by the broker, which would also give the account away
        $this->postJson('/forgot-password', ['email' => 'morador@example.com'])->assertOk()->assertExactJson($sent);
    }

    public function test_the_forgotten_password_email_is_in_portuguese_and_opens_the_frontend(): void
    {
        Notification::fake();
        $user = User::where('email', 'morador@example.com')->firstOrFail();

        $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            return str_starts_with($notification->url, config('oauth.frontend_url') . '/reset-password?token=')
                && str_contains($notification->url, 'email=' . urlencode($user->email))
                && $notification->toMail($user)->subject === 'Repor a password - Lar em Ordem';
        });
    }
}