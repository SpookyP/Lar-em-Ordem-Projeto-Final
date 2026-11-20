<?php

namespace App\Policies;

use App\Models\User\Partner;
use App\Models\User\User;

/**
 * Only editing is checked here (UpdateUserProfileInformation); creating and removing
 * the profile go through ProfileService.
 */
class PartnerPolicy
{
    /**
     * Only the owner can edit the profile.
     */
    public function update(User $user, Partner $partner): bool
    {
        return $partner->user_id === $user->id;
    }
}