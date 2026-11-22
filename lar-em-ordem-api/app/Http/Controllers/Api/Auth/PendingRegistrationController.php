<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\Services\Auth\PendingRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\Privacy\ConsentService;

/**
 * Sign-up with a password: the account is only created once the email is confirmed.
 * (Fortify's /register is turned off in config/fortify.php because it created the account straight away.)
 */
class PendingRegistrationController extends Controller
{
    public function __construct(
        private CreateNewUser $creator,
        private PendingRegistrationService $pending,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $email = (string) $request->input('email');

        // an existing account adding a profile with its password is handled at once:
        // the email already exists, so it was already confirmed
        if ($email !== '' && User::where('email', $email)->exists()) {
            $user = $this->creator->create($request->all());

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json([
                'status' => 'created',
                'message' => 'Conta criada.',
                'user' => $user->load(['roles', 'resident', 'service_provider', 'partner', 'condominium_admin']),
            ], 201);
        }

        // a deleted account (soft delete) keeps its email; warn user now rather than when the link is opened
        if ($email !== '' && User::onlyTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['Este email não pode ser usado.']]);
        }

        // same validation as the normal sign-up; only validated fields are kept
        $validated = $this->creator->validate($request->all());

        $this->pending->start([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'profile' => Arr::except($validated, ['name', 'email', 'password', 'role', ...ConsentService::SIGN_UP_FIELDS]),
            'consents' => ConsentService::fromSignUp($validated),
            'provider' => null,
            'provider_user_id' => null,
        ]);

        return response()->json([
            'status' => 'pending',
            'message' => 'Enviámos um email para confirmar o registo.',
        ], 202);
    }

    public function confirm(Request $request): JsonResponse
    {
        $token = $request->validate(
            ['token' => ['required', 'string', 'size:64']],
            ['token.required' => 'Link inválido.', 'token.size' => 'Link inválido ou expirado.'],
        )['token'];

        $user = $this->pending->confirm($token);

        if ($user) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        return response()->json(['message' => 'Conta criada. Já pode iniciar sessão.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        $this->pending->resend($email);

        // same answer whether or not this email has a pending sign-up
        return response()->json(['message' => 'Se existir um registo por confirmar, enviámos um novo link.'], 202);
    }
}