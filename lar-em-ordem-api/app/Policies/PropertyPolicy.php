<?php

namespace App\Policies;

use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyPolicy
{
    /** The user's active contract for the home, as a query; null when the user has no resident profile. */
    private function activeContract(User $user, Property $property): ?HasMany
    {
        return $user->resident
            ? $property->contracts()->where('resident_id', $user->resident->id)->active()
            : null;
    }

    /** Has an active contract for the home, as owner or tenant. */
    private function isOwner(User $user, Property $property): bool
    {
        return (bool) $this->activeContract($user, $property)?->exists();
    }

    public function viewAny(User $user): bool
    {
        return $user->resident !== null;
    }

    public function view(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->resident !== null;
    }

    public function update(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->isOwner($user, $property);
    }

    public function viewOptions(User $user): bool
    {
        return $user->resident !== null;
    }

    /**
     * The vault (deeds, IMI, insurance) and its alerts are only for the owner: a tenant has none for the home.
     */
    public function manageVault(User $user, Property $property): bool
    {
        return (bool) $this->activeContract($user, $property)
            ?->whereHas('residentType', fn ($type) => $type->where('type', 'Owner'))
            ->exists();
    }
}