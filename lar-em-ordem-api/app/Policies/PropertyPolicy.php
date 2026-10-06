<?php

namespace App\Policies;

use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Auth\Access\Response;

class PropertyPolicy
{
    public function isOwner(User $user, Property $property): bool
    {
        if (!$user->resident) {
            return false;
        }

        return $property->contracts()
            ->where('resident_id', $user->resident->id)
            ->where('is_active', true)
            ->exists();
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->resident !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->resident !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function terminateContract(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function viewOptions(User $user): bool
    {
        return $user->resident !== null;
    }
}
