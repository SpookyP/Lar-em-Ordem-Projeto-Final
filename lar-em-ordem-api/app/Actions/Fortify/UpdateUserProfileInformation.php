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

use App\Http\Requests\Resident\UpdateResidentRequest;
use App\Http\Requests\PartnerOffer\UpdatePartnerRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $rules = [
            'name' => ['sometimes','nullable', 'string', 'max:255'],
            'email' => [
                'sometimes','nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::in(['resident', 'service_provider', 'partner'])]
        ];

        $role = $input['role'];

        if (!$user->hasRole($role)) {
            throw ValidationException::withMessages([
                'message' => "The given data was invalid.",
                'errors' => [
                    'role' => ["User does not have a {$role} profile attached to this account."],
                ],
            ]);
        }

        match ($role) {
            'resident'         => Gate::authorize('update', $user->resident),
            'partner'          => Gate::authorize('update', $user->partner),
            'service_provider' => Gate::authorize('update', $user->service_provider),
            default            => abort(403),
        };

        $roleRules = match ($role) {
            'resident' => (new UpdateResidentRequest())->rules(),
            'partner' => (new UpdatePartnerRequest())->rules(),
            'service_provider' => (new UpdateServiceProviderRequest())->rules(),
            default => [],
        };

        Validator::make($input, array_merge($rules, $roleRules))->validateWithBag('updateProfileInformation');

        DB::transaction(function () use ($user, $input, $role) {
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

            $this->updateAssociatedProfile($user, $input, $role);
        });
    }

    /**
     * Update the given verified user's profile information.
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

    /**
     * Update the specific profile table linked to the user.
     */
    protected function updateAssociatedProfile(User $user, array $input, string $role): void
    {
        match ($role) {
            'resident' => $user->resident()->update([
                'nif'  => $input['nif'],
            ]),
            'partner' => $user->partner()->update([
                'company_name' => $input['company_name'],
                'nif'          => $input['nif'],
                'phone'        => $input['phone'],
                'website'      => $input['website'] ?? null,
                'description'  => $input['description'],
            ]),
            'service_provider' => $user->service_provider()->update([
                'company_name' => $input['company_name'],
                'nif'          => $input['nif'],
                'phone'        => $input['phone'],
                'email'        => $input['provider_email'],
                'description'  => $input['description'],
            ]),
            default => null,
        };
    }
}
