<?php

namespace App\Services\Condominium;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CondominiumAdminService
{
    public function __construct(private PersonalDataEraser $eraser) {}

    /**
     * Creates the admin profile and assigns the 'condominium_admin' role.
     * ProfileService checks whether the account already has this profile.
     *
     * @param array $data Validated data (contact, admin_type).
     * @param User $owner User who owns the profile.
     * @return CondominiumAdmin
     */
    public function create(array $data, User $owner): CondominiumAdmin
    {
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('condominium_admin');

            return CondominiumAdmin::create(['user_id' => $owner->id] + $data);
        });
    }

    /**
     * Updates the admin's details.
     *
     * @param CondominiumAdmin $admin Profile to update.
     * @param array $data Validated data.
     * @return CondominiumAdmin
     */
    public function update(CondominiumAdmin $admin, array $data): CondominiumAdmin
    {
        $admin->update($data);

        return $admin;
    }

    /**
     * Removes the profile and the role. Fails (422) while the user still manages condominiums,
     * because a condominium can't be left without an admin; it has to be transferred first.
     *
     * @param CondominiumAdmin $admin Profile to remove.
     * @return void
     */
    public function delete(CondominiumAdmin $admin): void
    {
        if ($admin->condominia()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Transfira primeiro os condomínios que administra.'],
            ]);
        }

        DB::transaction(function () use ($admin) {
            $this->eraser->eraseCondominiumAdmin($admin);
            $admin->delete();
            $admin->user->removeRole('condominium_admin');
        });
    }
}