<?php

namespace App\Policies;

use App\Models\Offer\Offer;
use App\Models\User\User;
use Illuminate\Auth\Access\Response;


class OfferPolicy
{
    /**
     * The owning partner always sees the offer; everyone else only while it is current.
     * For anyone else, an offer that isn't current answers 404.
     */
    public function view(User $user, Offer $offer): Response
    {
        if ($offer->partner->user_id === $user->id) {
            return Response::allow();
        }

        $isCurrent = Offer::current()
            ->where('id', $offer->id)
            ->exists();

        return $isCurrent
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('partner');
    }

    /**
     * Determine whether the user can update the model.
     */
   public function update(User $user, Offer $offer): bool
   {
        return $offer->partner->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
     public function delete(User $user, Offer $offer): bool
    {
        return $offer->partner->user_id === $user->id;
    }
}
