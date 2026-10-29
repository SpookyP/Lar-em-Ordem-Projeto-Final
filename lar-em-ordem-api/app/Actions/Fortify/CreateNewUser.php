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
use App\Http\Requests\Condominium\StoreCondominiumAdminRequest;
use Illuminate\Support\Arr;
use App\Services\Auth\ProfileService;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private ProfileService $profileService) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $role = $input['role'] ?? null;
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => $this->passwordRules(),
            'role'     => ['required', 'string', Rule::in(ProfileService::ROLES)],
        ];

        // o FormRequest do perfil dá as regras E as mensagens (senão os erros vinham com o texto genérico do Laravel)
        $roleRequest = match ($role) {
            'resident'         => new StoreResidentRequest(),
            'partner'          => new StorePartnerRequest(),
            'service_provider' => new StoreServiceProviderRequest(),
            'condominium_admin' => new StoreCondominiumAdminRequest(),
            default            => null, // role inválido: falha na regra 'role'
        };

        // só os campos validados seguem para o perfil (nunca o $input bruto)
        $validated = Validator::make(
            $input,
            array_merge($rules, $roleRequest?->rules() ?? []),
            $roleRequest?->messages() ?? []
        )->validate();

        $user = User::where('email', $input['email'])->first();

        if ($user) {
            if (!Hash::check($input['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => ['Invalid credentials for existing account.']
                ]);
            }
        }

            return DB::transaction(function () use ($input, $validated, $user, $role) {
            if (!$user) {
                $user = User::create([
                    'name'     => $input['name'],
                    'email'    => $input['email'],
                    'password' => Hash::make($input['password']),
                ]);
            }

            // perfil + role (e zonas/especialidades do prestador) — "já tem este perfil" dá 422
            $this->profileService->create($user, $role, Arr::except($validated, ['name', 'email', 'password', 'role']));

            return $user;
        });
    }
}

