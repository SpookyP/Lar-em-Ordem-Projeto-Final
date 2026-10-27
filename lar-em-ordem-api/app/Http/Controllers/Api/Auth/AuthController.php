<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Auth\ProfileService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth as AuthFacade;
use App\Http\Requests\Resident\StoreResidentRequest;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;


class AuthController extends Controller
{
    public function __construct(private ProfileService $profileService) {}
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!AuthFacade::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json(['user' => AuthFacade::user()]);
    }

    public function logout(Request $request)
    {
        AuthFacade::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load(['resident', 'partner', 'service_provider']);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'resident'         => $user->resident,
            'partner'          => $user->partner,
            'service_provider' => $user->service_provider,
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

        // o FormRequest do perfil dá as regras E as mensagens
        $makeRequest = function (string $class) use ($user) {
            $request = new $class();
            $request->setUserResolver(fn() => $user);
            return $request;
        };

        $roleRequest = match ($role) {
            'resident'         => $makeRequest(StoreResidentRequest::class),
            'partner'          => $makeRequest(StorePartnerRequest::class),
            'service_provider' => $makeRequest(StoreServiceProviderRequest::class),
        };

        $validated = $request->validate($roleRequest->rules(), $roleRequest->messages());

        $this->profileService->create($user, $role, $validated);

        return response()->json([
            'message' => "Profile '{$role}' added successfully.",
            'user'    => $user->load(['roles', 'resident', 'service_provider', 'partner']),
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
            'message' => "Profile '{$role}' removed successfully.",
            'user'    => $user->load(['roles', 'resident', 'service_provider', 'partner']),
        ]);
    }

    /**
     * Soft-delete the authenticated user's account and end the session.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        AuthFacade::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
