<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\User\User;

class CondominiumPolicy
{


    /** The list already filters to the condominiums the user manages (scopeManagedBy). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** View a condominium by id: admin only. Residents see it through their property (GET /properties/{id}/condominium). */
    public function view(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Only users with a condominium admin profile. */
    public function create(User $user): bool
    {
        return $user->condominium_admin !== null;
    }

    /** Edit (includes generating a new invite code). */
    public function update(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    public function delete(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Link a property to a condominium (with an invite code). */
    public function join(User $user): bool
    {
        return $user->resident !== null;
    }

    /** Remove a property: the admin (any property) or the resident (their own). */
    public function leave(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user) || $condominium->hasMember($user);
    }
    
    /** Accept a transfer: users with a condominium admin profile. */
    public function claim(User $user): bool
    {
        return $user->condominium_admin !== null;
    }
}