<?php

namespace Tests\Unit\Auth;

use App\Services\Auth\OAuthClient;
use App\Services\Auth\SocialLoginException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The OAuth protocol side of social login, with the provider faked (no network, no database).
 */
class OAuthClientTest extends TestCase
{
    private OAuthClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new OAuthClient();
    }

    private function config(bool $trustsEmailVerified = true): array
    {
        return [
            'client_id'             => 'cid',
            'client_secret'         => 'secret',
            'token_url'             => 'https://idp.test/token',
            'userinfo_url'          => 'https://idp.test/userinfo',
            'trusts_email_verified' => $trustsEmailVerified,
        ];
    }

    private function fakeProvider(array $userinfo, int $tokenStatus = 200, int $userinfoStatus = 200): void
    {
        // a fresh factory each time: with Http::fake() the first matching stub keeps winning, so re-faking would be ignored
        Http::swap(new HttpFactory());

        Http::fake([
            'idp.test/token'    => Http::response(['access_token' => 'tok'], $tokenStatus),
            'idp.test/userinfo' => Http::response($userinfo, $userinfoStatus),
        ]);
    }

    private function fetch(bool $trusts = true): array
    {
        return $this->client->fetchProfile('google', $this->config($trusts), 'the-code', 'the-verifier');
    }

    private function assertFails(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected a SocialLoginException.');
        } catch (SocialLoginException $e) {
            $this->assertSame('falhou', $e->reason);
        }
    }

    // ------------------------------------------------------------------------ PKCE

    public function test_pkce_challenge_matches_the_rfc_7636_example(): void
    {
        $this->assertSame(
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            $this->client->challenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk')
        );
    }

    public function test_verifiers_are_random_and_long_enough(): void
    {
        $a = $this->client->newVerifier();

        $this->assertGreaterThanOrEqual(43, strlen($a));
        $this->assertNotSame($a, $this->client->newVerifier());
    }

    public function test_authorization_url_carries_state_pkce_and_the_callback(): void
    {
        $url = $this->client->authorizationUrl('google', [
            'client_id'     => 'cid',
            'authorize_url' => 'https://idp.test/authorize',
            'scopes'        => 'openid email profile',
            'extra'         => ['prompt' => 'select_account'],
        ], 'state-1', 'verifier-1');

        $this->assertStringStartsWith('https://idp.test/authorize?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('cid', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertSame('state-1', $query['state']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame($this->client->challenge('verifier-1'), $query['code_challenge']);
        $this->assertSame('select_account', $query['prompt']);
        $this->assertSame(config('oauth.callback_base') . '/auth/google/callback', $query['redirect_uri']);
        $this->assertStringNotContainsString('verifier-1', $url);
    }

    // -------------------------------------------------------------------- providers

    public function test_unknown_or_unconfigured_providers_have_no_config(): void
    {
        $this->assertNull($this->client->config('github'));

        config(['oauth.providers.google.client_id' => null, 'oauth.providers.google.client_secret' => 'secret']);
        $this->assertNull($this->client->config('google'));

        config(['oauth.providers.google.client_id' => 'cid']);
        $this->assertIsArray($this->client->config('google'));
    }

    // --------------------------------------------------------------------- profile

    public function test_profile_is_normalised_and_the_code_exchange_uses_the_pkce_verifier(): void
    {
        $this->fakeProvider(['sub' => 'g-1', 'email' => '  Ana@Gmail.com ', 'email_verified' => true, 'name' => ' Ana ']);

        $this->assertSame(
            ['id' => 'g-1', 'email' => 'ana@gmail.com', 'email_verified' => true, 'name' => 'Ana'],
            $this->fetch()
        );

        Http::assertSent(fn (Request $r) => $r->url() === 'https://idp.test/token'
            && $r['grant_type'] === 'authorization_code'
            && $r['code'] === 'the-code'
            && $r['code_verifier'] === 'the-verifier');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://idp.test/userinfo'
            && $r->hasHeader('Authorization', 'Bearer tok'));
    }

    public function test_email_is_not_verified_when_the_provider_is_not_trusted_to_say_so(): void
    {
        $this->fakeProvider(['sub' => 'ms-1', 'email' => 'ana@outlook.com', 'email_verified' => true]);

        $this->assertFalse($this->fetch(trusts: false)['email_verified']);
    }

    public function test_only_a_real_true_counts_as_verified(): void
    {
        foreach (['true', 1, 'yes', null] as $claim) {
            $this->fakeProvider(['sub' => 'g-1', 'email' => 'ana@gmail.com', 'email_verified' => $claim]);

            $this->assertFalse($this->fetch()['email_verified'], 'claim: ' . json_encode($claim));
        }
    }

    public function test_invalid_emails_are_dropped(): void
    {
        foreach (['not-an-email', str_repeat('a', 250) . '@example.com', ['nested'], 42] as $email) {
            $this->fakeProvider(['sub' => 'g-1', 'email' => $email, 'name' => 'Ana']);

            $this->assertNull($this->fetch()['email'], 'email: ' . json_encode($email));
        }
    }

    public function test_name_falls_back_to_the_start_of_the_email(): void
    {
        $this->fakeProvider(['sub' => 'g-1', 'email' => 'ana.silva@gmail.com']);

        $this->assertSame('ana.silva', $this->fetch()['name']);
    }

    public function test_profile_without_a_subject_is_rejected(): void
    {
        foreach ([[], ['sub' => ''], ['sub' => 123], ['sub' => ['x']]] as $userinfo) {
            $this->fakeProvider($userinfo + ['email' => 'ana@gmail.com']);

            $this->assertFails(fn () => $this->fetch());
        }
    }

    public function test_provider_errors_become_a_generic_failure(): void
    {
        $this->fakeProvider(['sub' => 'g-1'], tokenStatus: 400);
        $this->assertFails(fn () => $this->fetch());

        $this->fakeProvider(['error' => 'server_error'], userinfoStatus: 500);
        $this->assertFails(fn () => $this->fetch());
    }

    public function test_token_response_without_an_access_token_is_a_failure(): void
    {
        Http::fake(['idp.test/token' => Http::response(['access_token' => ''])]);

        $this->assertFails(fn () => $this->fetch());
        Http::assertNotSent(fn (Request $r) => $r->url() === 'https://idp.test/userinfo');
    }
}