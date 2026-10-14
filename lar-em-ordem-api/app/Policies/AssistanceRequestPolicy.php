<?php

namespace App\Policies;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;

class AssistanceRequestPolicy
{
    /**
     * O SU tem acesso a tudo; para os restantes segue para o método da ação.
     *
     * @param User $user Utilizador autenticado.
     * @param string $ability Ação a autorizar.
     * @return bool|null true para o SU; null para continuar a verificação.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }

    /**
     * Só o dono do pedido o pode ver.
     */
    public function view(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->user_id === $user->id;
    }

    /**
     * Só residentes podem abrir pedidos.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('resident');
    }

    /**
     * Só o dono do pedido o pode editar.
     */
    public function update(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->user_id === $user->id;
    }

    /**
     * Só o dono do pedido o pode cancelar.
     */
    public function cancel(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->user_id === $user->id;
    }
}