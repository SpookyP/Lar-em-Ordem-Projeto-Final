<?php

namespace App\Policies;

use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\User\User;

class AssistanceRequestPolicy
{
     /**
      * Who can see the request: the owner, a provider who already sent a proposal,
      * or a provider the request is available to.
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
     * Only the request owner can see the proposals received.
     */
    public function viewProposals(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->isOwnedBy($user);
    }

    /**
     * Only residents can open requests.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('resident');
    }

    /**
     * Only the request owner can edit it.
     */
    public function update(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->isOwnedBy($user);
    }

    /**
     * Only the request owner can cancel it.
     */
    public function cancel(User $user, AssistanceRequest $assistanceRequest): bool
    {
        return $assistanceRequest->isOwnedBy($user);
    }

    /**
     * Only service providers can see the list of available requests.
     */
    public function viewAvailable(User $user): bool
    {
        return $user->hasRole('service_provider');
    }
}