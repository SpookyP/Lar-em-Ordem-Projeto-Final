<?php

namespace App\Actions\Fortify;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\Resident\StoreResidentRequest;
use App\Http\Requests\PartnerOffer\StorePartnerRequest;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $role = $input['role'] ?? null;;
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => $this->passwordRules(),
            'role'     => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])], // No Futuro Adicionar 'condominium_admin'
        ];

        $roleRules = match ($role) {
            'resident' => (new StoreResidentRequest())->rules(),
            'partner' => (new StorePartnerRequest())->rules(),
            'service_provider' => (new StoreServiceProviderRequest())->rules(),
            default => [],
        };

        Validator::make($input, array_merge($rules, $roleRules))->validate();

        $user = User::where('email', $input['email'])->first();

        if ($user) {
            if (!Hash::check($input['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'password' => ['Invalid credentials for existing account.'],
                    ],
                ]);
            }

            $alreadyHasProfile = match ($role) {
                'resident'         => $user->resident()->exists(),
                'service_provider' => $user->service_provider()->exists(),
                'partner'          => $user->partner()->exists(),
                default            => false,
            };

            if ($alreadyHasProfile) {
                throw ValidationException::withMessages([
                    'message' => "The given data was invalid.",
                    'errors' => [
                        'role' => ["User already has a {$role} profile."],
                    ],
                ]);
            }
        }

        return DB::transaction(function () use ($input, $user, $role) {
            if (!$user) {
                $user = User::create([
                    'name'     => $input['name'],
                    'email'    => $input['email'],
                    'password' => Hash::make($input['password']),
                ]);
            }

            if ($role) {
                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }

            match ($role) {
                'resident' => $user->resident()->create([
                    'nif'  => $input['nif'],
                ]),

                'partner' => $user->partner()->create([
                    'company_name'=> $input['company_name'],
                    'nif'         => $input['nif'],
                    'phone'       => $input['phone'],
                    'website'     => $input['website'] ?? null,
                    'description' => $input['description'],
                ]),

                'service_provider' => $user->service_provider()->create([
                    'company_name' => $input['company_name'],
                    'nif'          => $input['nif'],
                    'phone'        => $input['phone'],
                    'provider_email'        => $input['provider_email'],
                    'description'  => $input['description'],
                ]),

                default => null,
            };

            return $user;
        });
    }
}
