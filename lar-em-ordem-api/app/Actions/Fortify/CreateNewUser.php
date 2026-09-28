<?php

namespace App\Actions\Fortify;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\DB;

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
       $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'role'     => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner','condominium_admin'])],
        ];

        $roleRules = match ($input['role'] ?? null) {
            'resident' => [
                'nif' => ['required', 'string', 'digits:9', 'unique:residents,nif'],
            ],
            'partner' => [
                'nif'         => ['required', 'string', 'digits:9', 'unique:partners,nif'],
                'phone'       => ['required', 'string', 'max:15'],
                'description' => ['required', 'string'],
                'website'     => ['nullable', 'string', 'url'],
            ],
            'service_provider' => [
                'company_name' => ['required', 'string', 'max:255'],
                'nif'          => ['required', 'string', 'digits:9', 'unique:service_providers,nif'],
                'phone'        => ['required', 'string', 'max:15'],
                'provider_email' => ['required', 'string', 'email', 'unique:service_providers,email'],
                'description'  => ['required', 'string'],
            ],
            default => [],
        };

        Validator::make($input, array_merge($rules, $roleRules))->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name'     => $input['name'],
                'email'    => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            $user->assignRole($input['role']);

            match ($input['role']) {
                'resident' => $user->resident()->create([
                    'name'      => $user->name,
                    'nif'       => $input['nif'],
                ]),

                'partner' => $user->partner()->create([
                    'name'        => $user->name,
                    'nif'         => $input['nif'],
                    'phone'       => $input['phone'],
                    'website'     => $input['website'] ?? null,
                    'description' => $input['description'],
                ]),

                'service_provider' => $user->service_provider()->create([
                    'company_name' => $input['company_name'],
                    'nif'          => $input['nif'],
                    'phone'        => $input['phone'],
                    'email'        => $input['provider_email'],
                    'description'  => $input['description'],
                ]),
            };

            return $user;
        });
    }
}