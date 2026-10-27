<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fala com a Google/Microsoft (OAuth 2.0 "authorization code" com PKCE).
 * So trata do protocolo; quem decide que conta entra e o SocialLoginService.
 */
class OAuthClient
{
    /**
     * Configuracao do fornecedor, ou null se nao existir ou faltarem as chaves no .env.
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
     * PKCE: texto aleatorio que fica so na sessao do servidor.
     */
    public function newVerifier(): string
    {
        return Str::random(64);
    }

    /**
     * PKCE: o fornecedor recebe so o hash (S256); o texto original so o enviamos na troca do codigo.
     */
    public function challenge(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Endereco do fornecedor para onde mandamos o browser.
     */
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
     * Troca o codigo por um token (servidor para servidor) e le o perfil.
     * Devolve sempre o mesmo formato: id, email, email_verified, name.
     *
     * @throws SocialLoginException se o fornecedor falhar ou responder algo inesperado
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

        // "sub" e o id estavel do utilizador no fornecedor
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
            // so conta como verificado se o fornecedor e conhecido por o dizer E disse mesmo "true"
            'email_verified' => ($config['trusts_email_verified'] ?? false) === true
                && ($data['email_verified'] ?? false) === true,
            'name' => Str::limit($name, 255, ''),
        ];
    }

    /**
     * Ajuda a perceber porque falhou (segredo errado, URI errado...). Regista so o estado HTTP
     * e o codigo de erro do fornecedor; nunca o codigo de login, tokens ou segredos.
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