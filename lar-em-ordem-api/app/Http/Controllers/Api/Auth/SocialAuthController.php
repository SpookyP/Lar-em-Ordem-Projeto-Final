<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthClient;
use App\Services\Auth\SocialLoginException;
use App\Services\Auth\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Login com Google/Microsoft. O browser navega para estas rotas (nao sao pedidos axios):
 *   /auth/{fornecedor}/redirect  -> vai para a Google/Microsoft
 *   /auth/{fornecedor}/callback  -> volta de la; cria a sessao e regressa ao frontend
 */
class SocialAuthController extends Controller
{
    private const REASONS = [
        'cancelado',
        'conta_existente',
        'conta_desativada',
        'sem_email',
        'indisponivel',
        'falhou',
    ];

    public function __construct(
        private OAuthClient $oauth,
        private SocialLoginService $login,
    ) {}

    /**
     * Passo 1: guarda "state" e o verifier PKCE na sessao e envia o browser para o fornecedor.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $config = $this->oauth->config($provider);

        if (!$config) {
            return $this->failure('indisponivel');
        }

        $state = Str::random(40);
        $verifier = $this->oauth->newVerifier();

        $request->session()->put('oauth', [
            'provider' => $provider,
            'state' => $state,
            'verifier' => $verifier,
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->away($this->oauth->authorizationUrl($provider, $config, $state, $verifier));
    }

    /**
     * Passo 2: o fornecedor devolve o utilizador aqui com ?code e ?state.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        // pull = le e apaga: cada pedido de login so pode ser usado uma vez
        $saved = $request->session()->pull('oauth');
        $config = $this->oauth->config($provider);

        if (!$config || !$this->stateIsValid($request, $saved, $provider)) {
            return $this->failure('falhou');
        }

        // o utilizador carregou em "cancelar" no ecra do fornecedor
        if ($request->query('error') !== null) {
            return $this->failure('cancelado');
        }

        $code = $request->query('code');

        if (!is_string($code) || $code === '' || strlen($code) > 2048) {
            return $this->failure('falhou');
        }

        try {
            $profile = $this->oauth->fetchProfile($provider, $config, $code, $saved['verifier']);
            $user = $this->login->resolveUser($provider, $profile);
        } catch (SocialLoginException $e) {
            return $this->failure($e->reason);
        } catch (Throwable $e) {
            report($e);

            return $this->failure('falhou');
        }

         if ($user === null) {
            $request->session()->put('social_pending', $profile + [
                'provider' => $provider,
                'expires' => now()->addMinutes(15)->timestamp,
            ]);

            return redirect()->away(config('oauth.frontend_url') . '/register?social=1');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->away(config('oauth.frontend_url') . '/perfis');
    }

    /**
     * O "state" tem de ser o que guardamos na sessao, para o mesmo fornecedor, e ainda nao ter expirado.
     */
    private function stateIsValid(Request $request, mixed $saved, string $provider): bool
    {
        $received = $request->query('state');

        return is_array($saved)
            && ($saved['provider'] ?? null) === $provider
            && ($saved['expires'] ?? 0) >= time()
            && is_string($saved['state'] ?? null)
            && is_string($received)
            && hash_equals($saved['state'], $received);
    }

    /**
     * Volta ao login do frontend com um codigo fixo. Nunca mostramos texto vindo do fornecedor.
     */
    private function failure(string $reason): RedirectResponse
    {
        if (!in_array($reason, self::REASONS, true)) {
            $reason = 'falhou';
        }

        return redirect()->away(config('oauth.frontend_url') . '/login?erro=' . $reason);
    }
}