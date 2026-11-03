<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\User\User;

class CondominiumDocumentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }

    /** Lista pelo condomínio: só o administrador (o residente usa a lista pela casa). */
    public function viewAny(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** Ver/descarregar: administrador ou residente com casa no condomínio. */
    public function view(User $user, CondominiumDocument $document): bool
    {
        return $document->condominium->isManagedBy($user) || $document->condominium->hasMember($user);
    }

    /** Carregar documento: só o administrador. */
    public function create(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    public function update(User $user, CondominiumDocument $document): bool
    {
        return $document->condominium->isManagedBy($user);
    }

    public function delete(User $user, CondominiumDocument $document): bool
    {
        return $document->condominium->isManagedBy($user);
    }
}