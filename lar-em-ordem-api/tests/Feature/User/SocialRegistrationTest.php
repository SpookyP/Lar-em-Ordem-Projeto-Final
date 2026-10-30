<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RegistersWithConfirmation;
use Tests\TestCase;

/**
 * Last step of "Continue with Google/Microsoft" for someone without an account yet.
 * The identity comes from the server session, never from the request body.
 * Google proves the email, so the account is created at once; Microsoft does not,
 * so it waits for the emailed link like a password registration.
 */
class SocialRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use RegistersWithConfirmation;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]]);
    }

    private function pending(array $override = []): array
    {
        return array_merge([
            'provider' => 'google',
            'id' => 'g-123',
            'email' => 'ana@gmail.com',
            'email_verified' => true,
            'name' => 'Ana Social',
            'expires' => time() + 900,
        ], $override);
    }

    private function register(array $pending, array $body = ['role' => 'resident', 'nif' => '212345678'])
    {
        return $this->withSession(['social_pending' => $pending])
            ->postJson('/api/auth/social/register', $body);
    }

    // ------------------------------------------------------------ pending identity

    public function test_pending_endpoint_only_exposes_what_the_screen_needs(): void
    {
        $this->withSession(['social_pending' => $this->pending()])
            ->getJson('/api/auth/social/pending')
            ->assertOk()
            ->assertExactJson(['provider' => 'google', 'name' => 'Ana Social', 'email' => 'ana@gmail.com']);
    }

    public function test_pending_endpoint_returns_404_without_a_pending_identity(): void
    {
        $this->getJson('/api/auth/social/pending')->assertNotFound();
    }

    public function test_pending_endpoint_returns_404_once_the_identity_expired(): void
    {
        $this->withSession(['social_pending' => $this->pending(['expires' => time() - 1])])
            ->getJson('/api/auth/social/pending')
            ->assertNotFound();
    }

    // ------------------------------------------------------------------- Google

    public function test_google_creates_the_account_straight_away_and_signs_in(): void
    {

        $this->register($this->pending())
            ->assertCreated()
            ->assertJsonPath('status', 'created')
            ->assertSessionMissing('social_pending');

        $user = User::where('email', 'ana@gmail.com')->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('resident'));
        $this->assertSame('212345678', $user->resident->nif);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'g-123',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_name_and_email_come_from_the_session_not_from_the_request(): void
    {
        $this->register($this->pending(), [
            'role' => 'resident',
            'nif' => '212345678',
            'name' => 'Someone Else',
            'email' => 'attacker@example.com',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'ana@gmail.com', 'name' => 'Ana Social']);
        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.com']);
    }

    public function test_google_registration_fails_when_the_email_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'ana@gmail.com']);

        $this->register($this->pending())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['social']);

        $this->assertDatabaseCount('social_accounts', 0);
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- Microsoft

    public function test_microsoft_waits_for_the_emailed_link_before_creating_the_account(): void
    {
        Notification::fake();

        $this->register($this->pending([
            'provider' => 'microsoft',
            'id' => 'ms-123',
            'email' => 'ana@outlook.com',
            'email_verified' => false,
        ]))
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending')
            ->assertSessionMissing('social_pending');

        $this->assertDatabaseMissing('users', ['email' => 'ana@outlook.com']);
        $this->assertGuest();

        // opening the link (on any device) creates the account linked to that Microsoft identity
        $token = $this->sentToken();
        $this->postJson('/api/auth/register/confirm', ['token' => $token])->assertOk();

        $user = User::where('email', 'ana@outlook.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'microsoft',
            'provider_user_id' => 'ms-123',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_microsoft_registration_fails_when_the_email_already_has_an_account(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'ana@outlook.com']);

        $this->register($this->pending(['provider' => 'microsoft', 'email' => 'ana@outlook.com', 'email_verified' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['social']);

        Notification::assertNothingSent();
    }

    // --------------------------------------------------------------- validation

    public function test_registration_without_a_pending_identity_returns_422(): void
    {
        $this->postJson('/api/auth/social/register', ['role' => 'resident', 'nif' => '212345678'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['social']);
    }

    public function test_expired_identity_returns_422_and_creates_nothing(): void
    {
        $this->register($this->pending(['expires' => time() - 1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['social']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@gmail.com']);
    }

    public function test_unknown_or_privileged_role_is_rejected(): void
    {
        foreach (['condominium_admin', 'SU', 'admin', ''] as $role) {
            $this->register($this->pending(), ['role' => $role, 'nif' => '212345678'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['role']);
        }

        $this->assertDatabaseMissing('users', ['email' => 'ana@gmail.com']);
    }

    public function test_invalid_profile_data_returns_422_and_creates_nothing(): void
    {
        $this->register($this->pending(), ['role' => 'resident', 'nif' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nif']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@gmail.com']);
    }
}