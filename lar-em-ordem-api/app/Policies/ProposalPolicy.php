<?php

namespace App\Policies;

use App\Models\ServiceProvider\Proposal;
use App\Models\User\User;

class ProposalPolicy
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
     * Só prestadores podem enviar e listar as suas propostas.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('service_provider');
    }

    /**
     * Só o prestador dono da proposta a pode editar.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->serviceProvider->user_id === $user->id;
    }

    /**
     * Só o prestador dono da proposta a pode retirar.
     */
    public function withdraw(User $user, Proposal $proposal): bool
    {
        return $proposal->serviceProvider->user_id === $user->id;
    }

    /**
     * Só o dono do pedido pode aceitar uma proposta.
     */
    public function accept(User $user, Proposal $proposal): bool
    {
        return $proposal->assistanceRequest->isOwnedBy($user);
    }

    /**
     * Só o dono do pedido pode rejeitar uma proposta.
     */
     public function reject(User $user, Proposal $proposal): bool
    {
        return $proposal->assistanceRequest->isOwnedBy($user);
    }
}