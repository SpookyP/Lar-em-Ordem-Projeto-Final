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
     * Pode ver o pedido: o dono; um prestador que já enviou proposta;
     * ou um prestador para quem o pedido está disponível.
     */
    public function view(User $user, AssistanceRequest $assistanceRequest): bool
    {
        if ($assistanceRequest->user_id === $user->id) {
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

    /**
     * Só o dono do pedido o pode concluir.
     */
    public function close(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->user_id === $user->id;
    }

    /**
     * Só prestadores de serviços podem ver a lista de pedidos disponíveis.
     */
    public function viewAvailable(User $user): bool
    {
        return $user->hasRole('service_provider');
    }
}