<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Talks to Google/Microsoft (OAuth 2.0 authorization code flow with PKCE).
 * It only handles the protocol; SocialLoginService decides which account signs in.
 */
class OAuthClient
{
    /**
     * The provider's config, or null if it doesn't exist or its keys are missing from .env.
     */
    public function config(string $provider): ?array
    {
        $config = config("oauth.providers.$provider");

        if (!is_array($config) || empty($config['client_id']) || empty($config['client_secret'])) {
            return null;
        }

        return $config;
    }

    public function callbackUrl(string $provider): string
    {
        return config('oauth.callback_base') . "/auth/{$provider}/callback";
    }

    /**
     * PKCE: a random string that only lives in the server session.
     */
    public function newVerifier(): string
    {
        return Str::random(64);
    }

    /**
     * PKCE: the provider only gets the hash (S256); the original is only sent when exchanging the code.
     */
    public function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function authorizationUrl(string $provider, array $config, string $state, string $verifier): string
    {
        $query = array_merge($config['extra'] ?? [], [
            'client_id' => $config['client_id'],
            'redirect_uri' => $this->callbackUrl($provider),
            'response_type' => 'code',
            'scope' => $config['scopes'],
            'state' => $state,
            'code_challenge' => $this->challenge($verifier),
            'code_challenge_method' => 'S256',
        ]);

        return $config['authorize_url'] . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchanges the code for a token (server to server) and reads the profile.
     * Always returns the same shape whatever the provider.
     *
     * @return array{id: string, email: ?string, email_verified: bool, name: string}
     *
     * @throws SocialLoginException when the provider fails or answers something unexpected
     */
    public function fetchProfile(string $provider, array $config, string $code, string $verifier): array
    {
        try {
            $token = Http::asForm()->acceptJson()->timeout(10)->post($config['token_url'], [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->callbackUrl($provider),
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code_verifier' => $verifier,
            ]);

            $accessToken = $token->successful() ? $token->json('access_token') : null;

            if (!is_string($accessToken) || $accessToken === '') {
                $this->logFailure($provider, 'troca do codigo', $token);
                throw new SocialLoginException('falhou');
            }

            $info = Http::withToken($accessToken)->acceptJson()->timeout(10)->get($config['userinfo_url']);
        } catch (ConnectionException) {
            Log::warning('OAuth: sem ligacao ao fornecedor', ['provider' => $provider]);
            throw new SocialLoginException('falhou');
        }

        $data = $info->successful() ? $info->json() : null;

        if ($data === null) {
            $this->logFailure($provider, 'leitura do perfil', $info);
        }

        // "sub" is the user's stable id at the provider
        if (!is_array($data) || !is_string($data['sub'] ?? null) || $data['sub'] === '') {
            throw new SocialLoginException('falhou');
        }

        $email = is_string($data['email'] ?? null) ? Str::lower(trim($data['email'])) : null;
        if ($email !== null && (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $email = null;
        }

        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        if ($name === '' && $email !== null) {
            $name = Str::before($email, '@');
        }

        return [
            'id' => $data['sub'],
            'email' => $email,
            // only verified if the provider is trusted to say so AND actually said true
            'email_verified' => ($config['trusts_email_verified'] ?? false) === true
                && ($data['email_verified'] ?? false) === true,
            'name' => Str::limit($name, 255, ''),
        ];
    }

    /**
     * Helps to find out why it failed (wrong secret, wrong URI...). Logs only the HTTP status and the
     * provider's error code; never the sign-in code, tokens or secrets.
     */
    private function logFailure(string $provider, string $step, $response): void
    {
        $description = (string) $response->json('error_description');
        preg_match('/AADSTS\d+/', $description, $aadsts);

        Log::warning('OAuth: falhou a ' . $step, [
            'provider' => $provider,
            'status' => $response->status(),
            'error' => is_string($response->json('error')) ? $response->json('error') : null,
            'aadsts' => $aadsts[0] ?? null,
        ]);
    }
}