<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Auth\ProfileService;
use App\Services\Privacy\AccountErasureService;
use App\Services\Privacy\ConsentService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth as AuthFacade;

class AuthController extends Controller
{
    public function __construct(private ProfileService $profileService, private ConsentService $consents) {}
    
    public function user(Request $request)
    {
        $user = $request->user()->load(['roles', 'resident', 'service_provider', 'partner', 'condominium_admin','social_account']);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'social_provider' => $user->social_account?->provider,
            'roles' => $user->roles->pluck('name'),
            'resident'         => $user->resident,
            'partner'          => $user->partner,
            'service_provider' => $user->service_provider,
            'condominium_admin' => $user->condominium_admin,
            'privacy'          => $this->consents->summary($user),
        ]);
    }

    /**
     * Attach a new role and create the corresponding profile for the authenticated user.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $rules = $request->validate([
            'role' => ['required', 'string', Rule::in(ProfileService::ROLES)],
        ]);

        $role = $rules['role'];

        $roleRequest = ProfileService::requestFor($role, user: $user);

        $validated = $request->validate($roleRequest->rules(), $roleRequest->messages());

        $this->profileService->create($user, $role, $validated);

        return response()->json([
            'message' => 'Perfil adicionado.',
            'user'    => $user->load(['roles', 'resident', 'service_provider', 'partner', 'condominium_admin']),
        ], 201);
    }

    /**
     * Remove a role/profile from the authenticated user.
     */
    public function destroyProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(ProfileService::ROLES)],
        ]);

        $role = $validated['role'];

       $this->profileService->delete($user, $role);

        return response()->json([
            'message' => 'Perfil removido.',
            'user'    => $user->load(['roles', 'resident', 'service_provider', 'partner', 'condominium_admin']),
        ]);
    }

    /**
     * Erases the authenticated user's account and ends the session.
     */
    public function destroyAccount(Request $request, AccountErasureService $erasure): JsonResponse
    {
        // profiles emptied and the user anonymised in one transaction;
        // if a module refuses (422) nothing changes and the session stays
        $erasure->erase($request->user());

        AuthFacade::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Conta eliminada.']);
    }
}
