<?php

namespace App\Actions\Fortify;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Arr;
use App\Services\Auth\ProfileService;
use App\Services\Privacy\ConsentService;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private ProfileService $profileService,private ConsentService $consentService,) {}

    /**
     * Validate a newly registered user.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $input, bool $existingAccount = false): array
    {
        $role = $input['role'] ?? null;
        $rules = [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pM \'’-]+$/u'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => $existingAccount ? ['required', 'string'] : $this->passwordRules(),
            'role' => ['required', 'string', Rule::in(ProfileService::ROLES)],
        ];

        // an invalid role has no FormRequest and fails on the 'role' rule
        $roleRequest = is_string($role) ? ProfileService::requestFor($role) : null;

        // só os campos validados seguem para o perfil (nunca o $input bruto)
        return Validator::make(
            $input,
            array_merge($rules, $roleRequest?->rules() ?? [], ConsentService::signUpRules($existingAccount)),
            array_merge($roleRequest?->messages() ?? [], ConsentService::signUpMessages(), $this->passwordMessages())
        )->validate();
    }

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $role = $input['role'] ?? null;
        $user = User::where('email', $input['email'] ?? '')->first();
        
        if ($user && !Hash::check($input['password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['O email já está em uso, tente novamente.']
            ]);
        }

        $validated = $this->validate($input, existingAccount: (bool) $user);

        return DB::transaction(function () use ($input, $validated, $user, $role) {
            if (!$user) {
                $user = User::create([
                    'name' => $input['name'],
                    'email' => $input['email'],
                    'password' => Hash::make($input['password']),
                ]);
            }

            // perfil + role (e zonas/especialidades do prestador) — "já tem este perfil" dá 422
            $this->profileService->create($user, $role, Arr::except($validated, ['name', 'email', 'password', 'role', ...ConsentService::SIGN_UP_FIELDS]));
            $this->consentService->recordSignUp($user, ConsentService::fromSignUp($validated), 'signup');
            
            return $user;
        });
    }
}

