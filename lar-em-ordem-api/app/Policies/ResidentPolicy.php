<?php

namespace App\Policies;

use App\Models\User\Resident;
use App\Models\User\User;

class ResidentPolicy
{
    /**
     * Helper to check if the resident profile belongs to the authenticated user.
     */
    private function isSelf(User $user, ?Resident $resident): bool
    {
        if (!$user->resident || !$resident) {
            return false;
        }

        return $user->resident->id === $resident->id;
    }

    public function update(User $user, Resident $resident): bool
    {
        return $this->isSelf($user, $resident);
    }
}