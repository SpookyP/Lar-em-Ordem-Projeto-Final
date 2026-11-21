<?php

namespace App\Actions\Fortify;

use App\Models\User\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use App\Services\Auth\ProfileService;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private ProfileService $profileService) {}

    /**
     * Updates the account name and email and the profile of the given role.
     * A new email needs the current password and is verified again; social accounts can't change it.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $emailChanging = isset($input['email']) && $input['email'] !== $user->email;

        if ($emailChanging && $user->social_account()->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Esta conta usa o Google ou a Microsoft e o email não pode ser alterado.'],
            ]);
        }

        $rules = [
            'name' => ['sometimes', 'nullable', 'string', 'max:255', 'regex:/^[\pL\pM \'’-]+$/u'],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'current_password' => [Rule::requiredIf($emailChanging), 'nullable', 'string', 'current_password:web'],
            'role' => ['required', 'string', Rule::in(ProfileService::ROLES)]
        ];

        Validator::make($input, ['role' => $rules['role']])->validateWithBag('updateProfileInformation');

        $role = $input['role'];

        if (!$user->hasRole($role)) {
            throw ValidationException::withMessages([
                'role' => ["Esta conta não tem este perfil."],
            ]);
        }

        match ($role) {
            'resident'          => Gate::authorize('update', $user->resident),
            'partner'           => Gate::authorize('update', $user->partner),
            'service_provider'  => Gate::authorize('update', $user->service_provider),
            'condominium_admin' => Gate::authorize('update', $user->condominium_admin),
            default             => abort(403),
        };

        $roleRequest = ProfileService::requestFor($role, update: true, user: $user);

        // only validated fields go on, never the raw input (so user_id and the like can't be set)
        $validated = Validator::make($input, array_merge($rules, $roleRequest->rules()), array_merge($roleRequest->messages(), [
            'current_password.required'         => 'Indique a password atual para mudar o email.',
            'current_password.current_password' => 'A password atual não está correta.',
        ]))->validateWithBag('updateProfileInformation');

        DB::transaction(function () use ($user, $input, $validated, $role) {
            $newName = $input['name'] ?? $user->name;
            $newEmail = $input['email'] ?? $user->email;

            $emailChanged = $newEmail !== $user->email;

            if ($emailChanged && $user instanceof MustVerifyEmail) {
                $this->updateVerifiedUser($user, $newName, $newEmail);
            } else {
                $user->forceFill([
                    'name' => $newName,
                    'email' => $newEmail,
                ])->save();
            }

            $this->profileService->update($user, $role, Arr::except($validated, ['name', 'email', 'role', 'current_password']));
        });
    }

    /**
     * A changed email is marked as unverified and gets a new confirmation link.
     */
    protected function updateVerifiedUser(User $user, string $name, string $email): void
    {
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
    
}
