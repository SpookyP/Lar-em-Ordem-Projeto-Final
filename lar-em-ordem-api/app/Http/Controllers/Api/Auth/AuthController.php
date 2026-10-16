<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth as AuthFacade;
use App\Http\Requests\Resident\StoreResidentRequest;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;


class AuthController extends Controller
{
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
        $user = $request->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
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
            'role' => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])],
        ]);

        $role = $rules['role'];

        $roleRules = match ($role) {
            'resident' => (new StoreResidentRequest())->rules(),
            'partner' => (new StorePartnerRequest())->rules(),
            'service_provider' => (new StoreServiceProviderRequest())->rules(),
            default => [],
        };

        $validated = $request->validate($roleRules);

        $alreadyHasProfile = match ($role) {
            'resident'         => $user->resident()->exists(),
            'service_provider' => $user->service_provider()->exists(),
            'partner'          => $user->partner()->exists(),
            default            => false,
        };

        if ($alreadyHasProfile) {
            return response()->json([
                'message' => "The given data was invalid.",
                'errors' => [
                    'role' => ["You already have a {$role} profile attached to this account."],
                ],
            ], 422);
        }

        DB::transaction(function () use ($user, $validated, $role) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }

            match ($role) {
                'resident' => $user->resident()->create([
                    'nif'  => $validated['nif'],
                ]),

                'partner' => $user->partner()->create([
                    'company_name'=> $validated['company_name'],
                    'nif'         => $validated['nif'],
                    'phone'       => $validated['phone'],
                    'website'     => $validated['website'] ?? null,
                    'description' => $validated['description'],
                ]),

                'service_provider' => $user->service_provider()->create([
                    'company_name' => $validated['company_name'],
                    'nif'          => $validated['nif'],
                    'phone'        => $validated['phone'],
                    'email'        => $validated['provider_email'],
                    'description'  => $validated['description'],
                ]),

                default => null,
            };
        });

        return response()->json([
            'message' => "Profile '{$role}' added successfully.",
            'user'    => $user->load(['roles', 'resident', 'service_provider', 'partner']),
        ], 201);
    }
}
