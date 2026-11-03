<?php

namespace Tests\Feature\User;

use App\Models\User\SocialAccount;
use App\Models\User\User;
use App\Notifications\ConfirmRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Changing the email in the account settings: the new address has to be confirmed again and the old one is freed.
 * Accounts linked to Google/Microsoft cannot change it, because the provider is what confirmed it.
 */
class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function resident(): User
    {
        return User::where('email', 'morador@example.com')->firstOrFail();
    }

    private function update(User $user, array $extra = []): TestResponse
    {
        return $this->actingAs($user)->putJson('/user/profile-information', array_merge([
            'role' => 'resident',
            'nif'  => '298765432',
        ], $extra));
    }

    private function signedLink(User $user, string $emailForHash, int $minutes = 10): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id'   => $user->id,
            'hash' => sha1($emailForHash),
        ]);
    }

    // --------------------------------------------------------------- normal account

    public function test_changing_the_email_unverifies_it_and_sends_the_confirmation_link(): void
    {
        Notification::fake();
        $user = $this->resident();
        $old = $user->email;

        $this->update($user, ['email' => 'novo@example.com'])->assertOk();

        $user->refresh();
        $this->assertSame('novo@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('users', ['email' => $old]); // the old address is free again

        Notification::assertSentTo($user, ConfirmRegistration::class, function ($notification) use ($user) {
            parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);

            return str_starts_with($notification->url, config('oauth.frontend_url') . '/verify-email?')
                && ($query['id'] ?? null) === (string) $user->id
                && ($query['hash'] ?? null) === sha1('novo@example.com')
                && isset($query['expires'], $query['signature'])
                && $notification->minutes === 10;
        });
    }

    public function test_keeping_the_same_email_does_not_ask_for_a_new_confirmation(): void
    {
        Notification::fake();
        $user = $this->resident();

        $this->update($user, ['name' => 'Novo Nome', 'email' => $user->email])->assertOk();

        $user->refresh();
        $this->assertSame('Novo Nome', $user->name);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_the_link_in_the_email_confirms_the_new_address(): void
    {
        Notification::fake();
        $user = $this->resident();
        $this->update($user, ['email' => 'novo@example.com'])->assertOk();

        $this->postJson($this->signedLink($user, 'novo@example.com'))->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_a_link_made_for_the_old_address_is_rejected(): void
    {
        Notification::fake();
        $user = $this->resident();
        $old = $user->email;
        $this->update($user, ['email' => 'novo@example.com'])->assertOk();

        $this->postJson($this->signedLink($user, $old))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_expired_link_is_rejected(): void
    {
        Notification::fake();
        $user = $this->resident();
        $this->update($user, ['email' => 'novo@example.com'])->assertOk();
        $link = $this->signedLink($user, 'novo@example.com', minutes: 10);

        $this->travel(11)->minutes();

        $this->postJson($link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_tampered_link_is_rejected(): void
    {
        Notification::fake();
        $user = $this->resident();
        $this->update($user, ['email' => 'novo@example.com'])->assertOk();

        $this->postJson($this->signedLink($user, 'novo@example.com') . 'x')->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_sends_the_link_while_the_email_is_unverified(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/email/verification-notification')->assertOk();

        Notification::assertSentTo($user, ConfirmRegistration::class);
    }

    public function test_resend_does_nothing_once_the_email_is_verified(): void
    {
        Notification::fake();

        $this->actingAs($this->resident(), 'sanctum')->postJson('/api/email/verification-notification')->assertOk();

        Notification::assertNothingSent();
    }

    // ----------------------------------------------------------------- social account

    private function linkToGoogle(User $user): void
    {
        SocialAccount::create([
            'user_id'          => $user->id,
            'provider'         => 'google',
            'provider_user_id' => 'g-1',
            'email'            => $user->email,
        ]);
    }

    public function test_social_accounts_cannot_change_their_email(): void
    {
        Notification::fake();
        $user = $this->resident();
        $old = $user->email;
        $this->linkToGoogle($user);

        $this->update($user, ['email' => 'outro@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $user->refresh();
        $this->assertSame($old, $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_social_accounts_can_still_edit_everything_else(): void
    {
        Notification::fake();
        $user = $this->resident();
        $this->linkToGoogle($user);

        // the form sends the unchanged email back; leaving it out works too
        $this->update($user, ['name' => 'Ana Maria', 'email' => $user->email])->assertOk();
        $this->update($user, ['nif' => '212345678'])->assertOk();

        $this->assertSame('Ana Maria', $user->fresh()->name);
        $this->assertSame('212345678', $user->resident()->first()->nif);
        Notification::assertNothingSent();
    }

    public function test_user_endpoint_tells_the_frontend_which_provider_the_account_uses(): void
    {
        $social = $this->resident();
        $this->linkToGoogle($social);

        $this->actingAs($social, 'sanctum')->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('social_provider', 'google');

        $normal = User::where('email', 'residente@example.com')->firstOrFail();

        $this->actingAs($normal, 'sanctum')->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('social_provider', null);
    }

    public function test_user_endpoint_does_not_leak_provider_identifiers(): void
    {
        $user = $this->resident();
        $this->linkToGoogle($user);

        $body = $this->actingAs($user, 'sanctum')->getJson('/api/user')->assertOk()->getContent();

        $this->assertStringNotContainsString('provider_user_id', $body);
        $this->assertStringNotContainsString('g-1', $body);
    }
}