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

use App\Http\Requests\Resident\UpdateResidentRequest;
use App\Http\Requests\PartnerOffer\UpdatePartnerRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private ProfileService $profileService) {}

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
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::in(ProfileService::ROLES)]
        ];

        $role = $input['role'];

        if (!$user->hasRole($role)) {
            throw ValidationException::withMessages([
                'role' => ["User does not have a {$role} profile attached to this account."],
            ]);
        }

        match ($role) {
            'resident'         => Gate::authorize('update', $user->resident),
            'partner'          => Gate::authorize('update', $user->partner),
            'service_provider' => Gate::authorize('update', $user->service_provider),
            default            => abort(403),
        };

        $makeRules = function (string $class) use ($user) {
            $request = new $class();
            $request->setUserResolver(fn() => $user);
            return $request->rules();
        };

        $roleRules = match ($role) {
            'resident'         => $makeRules(UpdateResidentRequest::class),
            'partner'          => $makeRules(UpdatePartnerRequest::class),
            'service_provider' => $makeRules(UpdateServiceProviderRequest::class),
            default            => [],
        };

        // só os campos validados seguem para a frente (nunca o $input bruto: protege user_id, etc.)
        $validated = Validator::make($input, array_merge($rules, $roleRules))->validateWithBag('updateProfileInformation');

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

            $this->profileService->update($user, $role, Arr::except($validated, ['name', 'email', 'role']));
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
    
}
