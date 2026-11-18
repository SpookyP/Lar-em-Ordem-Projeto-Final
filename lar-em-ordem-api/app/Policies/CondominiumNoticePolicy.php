<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use App\Models\User\User;

class CondominiumNoticePolicy
{


    /** Histórico de avisos: só o administrador do condomínio. */
    public function viewAny(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Publicar aviso: só o administrador do condomínio. */
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

    /** Dar o "visto": residente com casa no condomínio. */
    public function markRead(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->hasMember($user);
    }

    /** Eliminar do "sino": residente com casa no condomínio. */
    public function dismiss(User $user, CondominiumNotice $notice): bool
    {
        return $notice->condominium->hasMember($user);
    }

}