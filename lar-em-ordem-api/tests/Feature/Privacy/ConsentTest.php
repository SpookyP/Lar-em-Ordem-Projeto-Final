<?php

namespace Tests\Feature\Privacy;

use App\Models\User\User;
use App\Services\Privacy\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\RegistersWithConfirmation;
use Tests\TestCase;

/**
 * Consent (RGPD art. 7): the privacy policy is accepted on every sign-up, data sharing is opt-in,
 * and both are recorded with the policy version and can be changed later.
 */
class ConsentTest extends TestCase
{
    use RefreshDatabase;
    use RegistersWithConfirmation;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Http::fake(); // the "uncompromised" password rule would call an external service
    }

    private function signUp(array $override = []): array
    {
        $password = 'Lr-Consent-2026#9a';

        return array_merge([
            'name' => 'Ana Consentimento',
            'email' => 'ana.consent@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'resident',
            'nif' => '212345678',
        ], $override);
    }

    private function consentsOf(string $email): array
    {
        return app(ConsentService::class)->current(User::where('email', $email)->firstOrFail());
    }

    private function socialSignUp(string $email, array $body): TestResponse
    {
        // the pending identity lives in the session, which Sanctum only starts for the SPA's domain
        return $this->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->withSession(['social_pending' => [
                'provider' => 'google',
                'id' => 'g-' . $email,
                'email' => $email,
                'email_verified' => true,
                'name' => 'Ana Google',
                'expires' => time() + 900,
            ]])
            ->postJson('/api/auth/social/register', $body);
    }

    // ------------------------------------------------------------------ sign-up

    public function test_sign_up_requires_accepting_the_privacy_policy(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', $this->signUp())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accept_privacy' => 'Tem de aceitar a Política de Privacidade para criar a conta.']);

        Notification::assertNothingSent();
    }

    public function test_a_confirmed_sign_up_records_the_policy_version_and_the_sharing_answer(): void
    {
        $this->registerAndConfirm($this->signUp(['accept_privacy' => true, 'data_sharing' => true]));

        $consents = $this->consentsOf('ana.consent@example.com');

        $this->assertTrue($consents[ConsentService::PRIVACY_POLICY]['granted']);
        $this->assertSame(config('privacy.policy_version'), $consents[ConsentService::PRIVACY_POLICY]['version']);
        $this->assertTrue($consents[ConsentService::DATA_SHARING]['granted']);
    }

    public function test_data_sharing_is_never_assumed(): void
    {
        $this->registerAndConfirm($this->signUp(['accept_privacy' => true]));

        $this->assertNull($this->consentsOf('ana.consent@example.com')[ConsentService::DATA_SHARING]);
    }

    public function test_the_consent_fields_never_reach_the_profile(): void
    {
        $this->registerAndConfirm($this->signUp(['accept_privacy' => true, 'data_sharing' => false]));

        $resident = User::where('email', 'ana.consent@example.com')->firstOrFail()->resident;

        $this->assertArrayNotHasKey('accept_privacy', $resident->getAttributes());
        $this->assertArrayNotHasKey('data_sharing', $resident->getAttributes());
    }

    public function test_the_version_recorded_is_the_one_shown_when_the_form_was_sent(): void
    {
        config(['privacy.policy_version' => 'v-shown']);
        $token = $this->registerWithoutConfirming($this->signUp(['accept_privacy' => true]));

        // the policy changes before the link is opened
        config(['privacy.policy_version' => 'v-later']);
        $this->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->postJson('/api/auth/register/confirm', ['token' => $token])
            ->assertOk();

        $this->assertSame('v-shown', $this->consentsOf('ana.consent@example.com')[ConsentService::PRIVACY_POLICY]['version']);
    }

    public function test_a_google_sign_up_records_the_consents_too(): void
    {
        $this->socialSignUp('ana.google@example.com', [
            'role' => 'resident',
            'nif' => '212345678',
            'accept_privacy' => true,
            'data_sharing' => false,
        ])->assertCreated();

        $consents = $this->consentsOf('ana.google@example.com');

        $this->assertTrue($consents[ConsentService::PRIVACY_POLICY]['granted']);
        $this->assertFalse($consents[ConsentService::DATA_SHARING]['granted']);
    }

    public function test_a_google_sign_up_without_the_policy_is_refused(): void
    {
        $this->socialSignUp('sem.consent@example.com', ['role' => 'resident', 'nif' => '212345678'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accept_privacy']);

        $this->assertFalse(User::where('email', 'sem.consent@example.com')->exists());
    }

    // --------------------------------------------------------- /api/user/privacy

    public function test_the_privacy_endpoint_needs_a_session(): void
    {
        $this->getJson('/api/user/privacy')->assertUnauthorized();
        $this->putJson('/api/user/privacy', ['data_sharing' => false])->assertUnauthorized();
    }

    public function test_a_user_can_withdraw_and_give_consent_and_the_history_is_kept(): void
    {
        $user = User::where('email', 'morador@example.com')->firstOrFail(); // the seeder gave consent
        $rows = fn () => $user->consents()->where('type', ConsentService::DATA_SHARING)->count();
        $before = $rows();

        $this->actingAs($user)->getJson('/api/user/privacy')
            ->assertOk()
            ->assertJsonPath('policy_version', config('privacy.policy_version'))
            ->assertJsonPath('consents.data_sharing.granted', true);

        $this->actingAs($user)->putJson('/api/user/privacy', ['data_sharing' => false])
            ->assertOk()
            ->assertJsonPath('consents.data_sharing.granted', false);

        // the same answer again adds nothing
        $this->actingAs($user)->putJson('/api/user/privacy', ['data_sharing' => false])->assertOk();
        $this->assertSame($before + 1, $rows());

        $this->actingAs($user)->putJson('/api/user/privacy', ['data_sharing' => true])
            ->assertJsonPath('consents.data_sharing.granted', true);
        $this->assertSame($before + 2, $rows());
    }

    public function test_the_privacy_endpoint_validates_its_input(): void
    {
        $user = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($user)->putJson('/api/user/privacy', ['data_sharing' => 'talvez'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_sharing']);
    }

    public function test_deleting_the_account_removes_its_consents(): void
    {
        $user = User::where('email', 'morador@example.com')->firstOrFail();

        $this->actingAs($user)
            ->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->deleteJson('/api/user')
            ->assertOk();

        $this->assertSame(0, User::withTrashed()->findOrFail($user->id)->consents()->count());
    }
}