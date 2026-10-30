<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\Resident\StoreResidentRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;
use App\Services\Auth\SocialLoginException;
use App\Services\Auth\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\Auth\PendingRegistrationService;
use App\Models\User\User;

/**
 * Fim do registo de quem entrou com Google/Microsoft e ainda nao tinha conta:
 * escolhe o tipo de conta e preenche os dados desse perfil.
 * Sao pedidos axios do frontend (por isso estao em api.php).
 */
class SocialRegisterController extends Controller
{
    public function __construct(private SocialLoginService $login,private PendingRegistrationService $registrations) {}

    /**
     * Diz ao frontend quem esta a registar-se (para mostrar "A continuar como ...").
     */
    public function show(Request $request): JsonResponse
    {
        $pending = $this->pending($request);

        if (!$pending) {
            return response()->json(['message' => 'Nao ha nenhum registo pendente.'], 404);
        }

        // so o que o ecra precisa de mostrar
        return response()->json([
            'provider' => $pending['provider'],
            'name' => $pending['name'],
            'email' => $pending['email'],
        ]);
    }

    /**
     * Cria a conta (com o perfil) e inicia sessao.
     */
    public function store(Request $request): JsonResponse
    {
        $pending = $this->pending($request);

        if (!$pending) {
            throw ValidationException::withMessages([
                'social' => ['O registo expirou. Volte a entrar com Google ou Microsoft.'],
            ]);
        }

        $role = $request->validate([
            'role' => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])],
        ])['role'];

        // as mesmas regras do registo normal. Nome e email NAO vem do pedido:
        // vem so da identidade guardada no servidor
        $rules = match ($role) {
            'resident' => (new StoreResidentRequest())->rules(),
            'partner' => (new StorePartnerRequest())->rules(),
            'service_provider' => (new StoreServiceProviderRequest())->rules(),
        };
        $data = $request->validate($rules);

        if (!($pending['email_verified'] ?? false)) {
            if (User::withTrashed()->where('email', $pending['email'])->exists()) {
                throw ValidationException::withMessages([
                    'social' => ['Já existe uma conta com este email. Entre com a sua password.'],
                ]);
            }

            $this->registrations->start([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => null,
                'role' => $role,
                'profile' => $data,
                'provider' => $pending['provider'],
                'provider_user_id' => $pending['id'],
            ]);

            $request->session()->forget('social_pending');

            return response()->json([
                'status' => 'pending',
                'message' => 'Enviámos um email para confirmar o registo.',
            ], 202);
        }

        try {
            $user = $this->login->registerFromPending($pending, $role, $data);
        } catch (SocialLoginException) {
            throw ValidationException::withMessages([
                'social' => ['Já existe uma conta com este email. Entre com a sua password.'],
            ]);
        }

        Auth::guard('web')->login($user);
        $request->session()->forget('social_pending');
        $request->session()->regenerate();

        return response()->json(['message' => 'Conta criada.'], 201);
    }

    /**
     * Identidade vinda da Google/Microsoft, guardada na sessao do servidor ate ao fim do registo.
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('social_pending');

        if (!is_array($pending) || ($pending['expires'] ?? 0) < time()) {
            $request->session()->forget('social_pending');

            return null;
        }

        return $pending;
    }
}