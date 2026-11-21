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
 * Social sign-in. The browser navigates to these routes (they aren't axios requests):
 *   /auth/{provider}/redirect  -> goes to Google/Microsoft
 *   /auth/{provider}/callback  -> comes back, starts the session and returns to the frontend
 */
class SocialAuthController extends Controller
{
    // the codes the frontend login page knows how to show
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
     * Step 1: keeps the state and the PKCE verifier in the session and sends the browser to the provider.
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
     * Step 2: the provider sends the user back here with ?code and ?state.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        // pull reads and deletes: each sign-in attempt can only be used once
        $saved = $request->session()->pull('oauth');
        $config = $this->oauth->config($provider);

        if (!$config || !$this->stateIsValid($request, $saved, $provider)) {
            return $this->failure('falhou');
        }

        // the user pressed cancel on the provider's screen
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

        // no account yet: the identity waits in the session for the last step (SocialRegisterController)
         if ($user === null) {
            $request->session()->put('social_pending', $profile + [
                'provider' => $provider,
                'expires' => now()->addMinutes(15)->timestamp,
            ]);

            return redirect()->away(config('oauth.frontend_url') . '/register?social=1');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->away(config('oauth.frontend_url') . '/profiles');
    }

    /**
     * The state must be the one saved in the session, for the same provider, and not expired.
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
     * Back to the frontend login with a fixed code; text from the provider is never shown.
     */
    private function failure(string $reason): RedirectResponse
    {
        if (!in_array($reason, self::REASONS, true)) {
            $reason = 'falhou';
        }

        return redirect()->away(config('oauth.frontend_url') . '/login?erro=' . $reason);
    }
}