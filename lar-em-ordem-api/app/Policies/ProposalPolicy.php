<?php

namespace App\Policies;

use App\Models\ServiceProvider\Proposal;
use App\Models\User\User;

class ProposalPolicy
{
    /**
     * Only service providers can send and list their proposals.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('service_provider');
    }

    /**
     * Only the provider who owns the proposal can edit it.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->serviceProvider?->user_id === $user->id;
    }

    /**
     * Only the provider who owns the proposal can withdraw it.
     */
    public function withdraw(User $user, Proposal $proposal): bool
    {
        return $proposal->serviceProvider?->user_id === $user->id;
    }

    /**
     * Only the request owner can accept a proposal.
     */
    public function accept(User $user, Proposal $proposal): bool
    {
        return $proposal->assistanceRequest->isOwnedBy($user);
    }

    /**
     * Only the request owner can reject a proposal.
     */
     public function reject(User $user, Proposal $proposal): bool
    {
        return $proposal->assistanceRequest->isOwnedBy($user);
    }
}