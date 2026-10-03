<?php

namespace App\Policies;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;


class ServiceProviderPolicy
{
     public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }
    /**
     * Determine whether the user can view any models.
     */
     public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
     public function view(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('service_provider');
    }

    /**
     * Determine whether the user can update the model.
     */
     public function update(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return $serviceProvider->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return $serviceProvider->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
     public function forceDelete(User $user, ServiceProviderModel $serviceProvider): bool
    {
        return false;
    }
}
