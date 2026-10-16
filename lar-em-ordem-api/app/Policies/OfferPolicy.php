<?php

namespace App\Policies;

use App\Models\Offer\Offer;
use App\Models\User\User;
use Illuminate\Auth\Access\Response;


class OfferPolicy
{
     public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('SU') ? true : null;
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * O parceiro dono vê sempre a oferta; os restantes só a veem se estiver em vigor.
     * Para quem não é dono, uma oferta fora de vigor responde 404.
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

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Offer $offer): bool
    {
        return false;
    }
}
