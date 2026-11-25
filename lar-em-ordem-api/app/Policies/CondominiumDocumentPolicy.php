<?php

namespace App\Policies;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\User\User;

class CondominiumDocumentPolicy
{


    /** List by condominium: admin only (residents use the list by property). */
    public function viewAny(User $user, Condominium $condominium): bool
    {
        return $condominium->isManagedBy($user);
    }

    /** View/download: the admin or a resident with a property in the condominium. */
    public function view(User $user, CondominiumDocument $document): bool
    {
        return $document->condominium->isManagedBy($user) || $document->condominium->hasMember($user);
    }

    /** Upload a document: admin only. */
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