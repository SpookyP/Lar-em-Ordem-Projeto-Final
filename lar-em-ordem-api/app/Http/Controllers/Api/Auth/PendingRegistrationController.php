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

/**
 * Registo com password: a conta so e criada depois de confirmar o email.
 * (O /register do Fortify esta desligado em config/fortify.php: criava a conta logo.)
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

        // quem ja tem conta e acrescenta um perfil com a mesma password mantem o comportamento de sempre
        // (o email ja existe, logo ja foi confirmado)
        if ($email !== '' && User::where('email', $email)->exists()) {
            $user = $this->creator->create($request->all());

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json([
                'status' => 'created',
                'message' => 'Operation successful.',
                'user' => $user->load(['roles', 'resident', 'service_provider', 'partner', 'condominium_admin']),
            ], 201);
        }

        // conta eliminada (soft delete): o email nao pode ser reutilizado; avisa logo e nao so ao abrir o link
        if ($email !== '' && User::onlyTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['Este email não pode ser usado.']]);
        }

        // mesmas validacoes do registo normal; so os campos validados seguem
        $validated = $this->creator->validate($request->all());

        $this->pending->start([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'profile' => Arr::except($validated, ['name', 'email', 'password', 'role']),
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

        $this->pending->confirm($token);

        return response()->json(['message' => 'Conta criada. Já pode iniciar sessão.']);
    }

    public function resend(Request $request): JsonResponse
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:255']])['email'];

        $this->pending->resend($email);

        // a mesma resposta exista ou nao um registo para este email
        return response()->json(['message' => 'Se existir um registo por confirmar, enviámos um novo link.'], 202);
    }
}