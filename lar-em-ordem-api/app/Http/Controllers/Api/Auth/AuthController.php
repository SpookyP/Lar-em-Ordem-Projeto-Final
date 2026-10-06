<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth as AuthFacade;

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

        $rules = [
            'role' => ['nullable', 'string', Rule::in(['resident', 'service_provider', 'partner'])],
        ];

        $role = !empty($request->input('role')) ? $request->input('role') : null;

        $roleRules = match ($role) {
            'resident' => [
                'nif' => ['required', 'string', 'digits:9'],
            ],
            'partner' => [
                'nif'         => ['required', 'string', 'digits:9'],
                'phone'       => ['required', 'string', 'max:15'],
                'description' => ['required', 'string'],
                'website'     => ['nullable', 'string', 'url'],
            ],
            'service_provider' => [
                'company_name'   => ['required', 'string', 'max:255'],
                'nif'            => ['required', 'string', 'digits:9'],
                'phone'          => ['required', 'string', 'max:15'],
                'provider_email' => ['required', 'string', 'email'],
                'description'    => ['required', 'string'],
            ],
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
            throw ValidationException::withMessages([
                'role' => ["You already have a {$role} profile attached to this account."],
            ]);
        }

        if ($role === 'service_provider') {
            $emailExists = DB::table('service_providers')
                ->where('email', $validated['provider_email'])
                ->exists();

            if ($emailExists) {
                throw ValidationException::withMessages([
                    'provider_email' => ['The provider email has already been taken.'],
                ]);
            }
        }

        DB::transaction(function () use ($user, $validated, $role) {
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }

            match ($role) {
                'resident' => $user->resident()->create([
                    'name' => $user->name,
                    'nif'  => $validated['nif'],
                ]),

                'partner' => $user->partner()->create([
                    'name'        => $user->name,
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
