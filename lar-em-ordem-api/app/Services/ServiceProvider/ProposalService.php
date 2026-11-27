<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProposalService
{

     /**
      * Returns the proposals a provider sent, newest first,
      * with the request each one answers, paginated. Optionally only one status.
      *
      * @param ServiceProviderModel $provider The provider.
      * @param int $perPage Proposals per page.
      * @param string|null $status Status (pending/expired/accepted/rejected/withdrawn), or null for all.
      * @return LengthAwarePaginator
      */
    public function listByProvider(ServiceProviderModel $provider, int $perPage = 5, ?string $status = null): LengthAwarePaginator
    {
        return $provider->proposals()
            ->when($status, fn ($q) => $q->status($status))
            ->with([
                'assistanceRequest.property.address',
                'assistanceRequest.problemCategory',
                'assistanceRequest.acceptedProposal.serviceProvider',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Sends a proposal to a request.
     * A provider can only send one per request, and the request has to be available to them.
     *
     * @param AssistanceRequest $assistanceRequest Request being answered.
     * @param ServiceProviderModel $provider Provider sending the proposal.
     * @param array $data Validated proposal data.
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
     * Edits a proposal. Only while it is pending.
     *
     * @param Proposal $proposal Proposal to edit.
     * @param array $data Validated data for the update.
     * @return Proposal
     */
    public function update(Proposal $proposal, array $data): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível editar propostas pendentes.');

        $proposal->update($data);

        return $proposal->load('serviceProvider');
    }

    /**
     * Withdraws a pending proposal.
     *
     * @param Proposal $proposal Proposal to withdraw.
     * @return Proposal
     */
    public function withdraw(Proposal $proposal): Proposal
    {
        $this->ensurePending($proposal, 'Só é possível retirar propostas pendentes.');

        $proposal->update(['status' => 'withdrawn']);

        return $proposal;
    }

    /**
     * Accepts a proposal: the other pending proposals of the same request are rejected
     * and the request becomes 'closed'. All in one transaction with the request locked,
     * so if two proposals are accepted at the same time the second one finds the request closed.
     *
     * @param Proposal $proposal Proposal to accept.
     * @return Proposal
     */
    public function accept(Proposal $proposal): Proposal
    {
        DB::transaction(function () use ($proposal) {
            $assistanceRequest = AssistanceRequest::lockForUpdate()->findOrFail($proposal->assistance_request_id);

            $this->ensurePending($proposal, 'Só é possível aceitar propostas pendentes.');

            if (! $assistanceRequest->isOpen()) {
                throw ValidationException::withMessages([
                    'status' => 'O pedido já não está aberto.',
                ]);
            }

            if ($proposal->isExpired()) {
                throw ValidationException::withMessages([
                    'validity' => 'Esta proposta já expirou.',
                ]);
            }

            $proposal->update([
                'status'        => 'accepted',
                'response_date' => now(),
            ]);

            $assistanceRequest->proposals()
                ->where('id', '!=', $proposal->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'response_date' => now()]);

            $assistanceRequest->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]);
        });

        return $proposal->refresh()->load('serviceProvider');
    }

    /**
     * Rejects a pending proposal without accepting another one: the request stays open.
     *
     * @param Proposal $proposal Proposal to reject.
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
     * Makes sure the proposal is pending.
     *
     * @param Proposal $proposal The proposal.
     * @param string $message Error message to return.
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