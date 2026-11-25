<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use App\Models\User\User;

class CondominiumNoticePolicy
{


    /** Notice history: the condominium's admin only. */
    public function viewAny(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Publish a notice: the condominium's admin only. */
    public function create(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    public function update(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->isManagedBy($user);
    }

    public function delete(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->isManagedBy($user);
    }

    /** Mark as seen: a resident with a property in the condominium. */
    public function markRead(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->hasMember($user);
    }

    /** Dismiss from the bell: a resident with a property in the condominium. */
    public function dismiss(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->hasMember($user);
    }

}