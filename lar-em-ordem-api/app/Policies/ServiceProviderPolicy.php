<?php

namespace App\Policies;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;

/**
 * Only editing is checked here (UpdateUserProfileInformation); creating and removing
 * the profile go through ProfileService.
 */
class ServiceProviderPolicy
{
    /**
     * Only the owner can edit the profile.
     */
    public function update(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return $serviceProvider->user_id === $user->id;
    }
}