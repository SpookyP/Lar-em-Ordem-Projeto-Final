<?php

namespace App\Policies;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;

class AssistanceRequestPolicy
{
     /**
     * Pode ver o pedido: o dono; um prestador que já enviou proposta;
     * ou um prestador para quem o pedido está disponível.
     */
    public function view(User $user, AssistanceRequest $assistanceRequest): bool
    {
         if ($assistanceRequest->isOwnedBy($user)) {
            return true;
        }

        $provider = $user->service_provider;

        if ($provider === null) {
            return false;
        }

        $hasProposal = $assistanceRequest->proposals()
            ->where('service_provider_id', $provider->id)
            ->exists();

        return $hasProposal
            || AssistanceRequest::availableFor($provider)
                ->where('id', $assistanceRequest->id)
                ->exists();
    }

    /**
     * Só o dono do pedido pode ver as propostas recebidas.
     */
    public function viewProposals(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->isOwnedBy($user);
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
        return $assistanceRequest->isOwnedBy($user);
    }

    /**
     * Só o dono do pedido o pode cancelar.
     */
    public function cancel(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->isOwnedBy($user);
    }

    /**
     * Só prestadores de serviços podem ver a lista de pedidos disponíveis.
     */
    public function viewAvailable(User $user): bool
    {
        return $user->hasRole('service_provider');
    }
}