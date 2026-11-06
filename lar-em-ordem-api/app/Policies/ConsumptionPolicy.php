<?php

namespace App\Policies;

use App\Models\Invoice\Consumption;
use App\Models\User\User;

class ConsumptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->resident !== null;
    }

    public function view(User $user, Consumption $consumption): bool
    {
        return $user->resident !== null
            && $consumption->invoice?->resident_id === $user->resident->id;
    }
}