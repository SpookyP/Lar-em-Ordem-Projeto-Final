<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\User\User;

class CondominiumPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }

    /** A listagem já filtra pelo que o utilizador pode ver (scopeVisibleTo). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Administrador ou residente com casa no condomínio. */
    public function view(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user) || $condominium->hasMember($user);
    }

    /** Só quem tem perfil de administrador de condomínio. */
    public function create(User $user): bool
    {
        return $user->condominium_admin !== null;
    }

    /** Editar (inclui gerar novo código de convite). */
    public function update(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    public function delete(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Ligar uma casa a um condomínio (por código de convite). */
    public function join(User $user): bool
    {
        return $user->resident !== null;
    }

    /** Retirar uma casa: o administrador (qualquer casa) ou o residente (a sua). */
    public function leave(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user) || $condominium->hasMember($user);
    }
    
    /** Aceitar uma transferência: quem tem perfil de administrador de condomínio. */
    public function claim(User $user): bool
    {
        return $user->condominium_admin !== null;
    }
}