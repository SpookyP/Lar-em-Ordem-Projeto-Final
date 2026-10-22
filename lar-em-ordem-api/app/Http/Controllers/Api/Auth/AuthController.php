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
            'role' => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])],
        ]);

        $role = $rules['role'];

        $makeRules = function (string $class) use ($user) {
            $request = new $class();
            $request->setUserResolver(fn() => $user);
            return $request->rules();
        };

        $roleRules = match ($role) {
            'resident'         => $makeRules(StoreResidentRequest::class),
            'partner'          => $makeRules(StorePartnerRequest::class),
            'service_provider' => $makeRules(StoreServiceProviderRequest::class),
            default            => [],
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
                    'provider_email' => $validated['provider_email']  ?? null,
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

    /**
     * Remove a role/profile from the authenticated user.
     */

    public function destroyProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])],
        ]);

        $role = $validated['role'];

        $hasProfile = match ($role) {
            'resident'         => $user->resident()->exists(),
            'service_provider' => $user->service_provider()->exists(),
            'partner'          => $user->partner()->exists(),
            default            => false,
        };

        if (!$hasProfile) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['role' => ["You do not have a {$role} profile attached to this account."]],
            ], 422);
        }

        if ($user->roles()->count() <= 1) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => ['role' => ['You cannot remove your only profile. Delete the account instead.']],
            ], 422);
        }

        DB::transaction(function () use ($user, $role) {
            match ($role) {
                'resident'         => $user->resident()->delete(),
                'partner'          => $user->partner()->delete(),
                'service_provider' => $user->service_provider()->delete(),
                default            => null,
            };

            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        });

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
