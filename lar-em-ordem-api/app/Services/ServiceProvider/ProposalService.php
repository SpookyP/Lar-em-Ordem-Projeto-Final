<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalService
{
     /**
     * Devolve as propostas recebidas por um pedido, da mais recente para a mais antiga.
     *
     * @param AssistanceRequest $assistanceRequest Instância do pedido.
     * @return Collection
     */
    public function listByRequest(AssistanceRequest $assistanceRequest): Collection
    {
        return $assistanceRequest->proposals()
            ->with('serviceProvider')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Devolve as propostas enviadas por um prestador, da mais recente para a mais antiga,
     * com o pedido a que respondem.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @return Collection
     */
    public function listByProvider(ServiceProviderModel $provider): Collection
    {
        return $provider->proposals()
            ->with([
                'assistanceRequest.property.address',
                'assistanceRequest.problemCategory',
                'assistanceRequest.acceptedProposal.serviceProvider',
            ])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Envia uma proposta a um pedido.
     * O prestador só pode enviar uma por pedido, e o pedido tem de estar disponível para ele.
     *
     * @param AssistanceRequest $assistanceRequest Pedido a que responde.
     * @param ServiceProviderModel $provider Prestador que envia a proposta.
     * @param array $data Dados validados da proposta.
     * @return Proposal
     */
    public function create(AssistanceRequest $assistanceRequest, ServiceProviderModel $provider, array $data): Proposal
    {
        $alreadySent = $assistanceRequest->proposals()
            ->where('service_provider_id', $provider->id)
            ->exists();

        if ($alreadySent) {
            throw ValidationException::withMessages([
                'assistance_request' => 'Já enviou uma proposta para este pedido.',
            ]);
        }

        $available = AssistanceRequest::availableFor($provider)
            ->where('id', $assistanceRequest->id)
            ->exists();

        if (! $available) {
            throw ValidationException::withMessages([
                'assistance_request' => 'Este pedido não está disponível para receber a sua proposta.',
            ]);
        }

        $proposal = $assistanceRequest->proposals()->create($data + [
            'service_provider_id' => $provider->id,
        ]);

        return $proposal->load('serviceProvider');
    }

    /**
     * Edita uma proposta. Só é possível enquanto estiver pendente.
     *
     * @param Proposal $proposal Proposta a editar.
     * @param array $data Dados validados para a atualização.
     * @return Proposal
     */
    public function update(Proposal $proposal, array $data): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível editar propostas pendentes.');

        $proposal->update($data);

        return $proposal->load('serviceProvider');
    }

    /**
     * Retira uma proposta pendente.
     *
     * @param Proposal $proposal Proposta a retirar.
     * @return Proposal
     */
    public function withdraw(Proposal $proposal): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível retirar propostas pendentes.');

        $proposal->update(['status' => 'withdrawn']);

        return $proposal;
    }

    /**
     * Aceita uma proposta: as outras pendentes do mesmo pedido são rejeitadas
     * e o pedido passa a 'in_progress'. Tudo numa transação.
     *
     * @param Proposal $proposal Proposta a aceitar.
     * @return Proposal
     */
    public function accept(Proposal $proposal): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível aceitar propostas pendentes.');

        $assistanceRequest = $proposal->assistanceRequest;

        if (! $assistanceRequest->isOpen()) {
            throw ValidationException::withMessages([
                'status' => 'O pedido já não está aberto.',
            ]);
        }

        if ($proposal->validity->lt(today())) {
            throw ValidationException::withMessages([
                'validity' => 'Esta proposta já expirou.',
            ]);
        }

        DB::transaction(function () use ($proposal, $assistanceRequest) {
            $proposal->update([
                'status'        => 'accepted',
                'response_date' => now(),
            ]);

            $assistanceRequest->proposals()
                ->where('id', '!=', $proposal->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'response_date' => now()]);

            $assistanceRequest->update(['status' => 'in_progress']);
        });

        return $proposal->load('serviceProvider');
    }

    /**
     * Rejeita uma proposta pendente.
     *
     * @param Proposal $proposal Proposta a rejeitar.
     * @return Proposal
     */
    public function reject(Proposal $proposal): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível rejeitar propostas pendentes.');

        $proposal->update([
            'status'        => 'rejected',
            'response_date' => now(),
        ]);

        return $proposal;
    }

    /**
     * Garante que a proposta está pendente.
     *
     * @param Proposal $proposal Instância da proposta.
     * @param string $message Mensagem de erro a devolver.
     * @return void
     */
    private function ensurePending(Proposal $proposal, string $message): void
    {
        if (! $proposal->isPending()) {
            throw ValidationException::withMessages([
                'status' => $message,
            ]);
        }
    }
}