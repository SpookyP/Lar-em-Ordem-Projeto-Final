<?php

namespace App\Policies;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\User;

class CondominiumAdminPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }

    public function view(User $user, CondominiumAdmin $admin): bool
    {
        return $admin->user_id === $user->id;
    }

    public function update(User $user, CondominiumAdmin $admin): bool
    {
        return $admin->user_id === $user->id;
    }

    public function delete(User $user, CondominiumAdmin $admin): bool
    {
        return $admin->user_id === $user->id;
    }
}