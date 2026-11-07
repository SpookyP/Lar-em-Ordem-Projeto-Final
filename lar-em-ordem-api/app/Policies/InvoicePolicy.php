<?php

namespace App\Policies;

use App\Models\Invoice\Invoice;
use App\Models\User\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->resident !== null;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->resident !== null
            && $invoice->resident_id === $user->resident->id;
    }

    public function create(User $user): bool
    {
        return $user->resident !== null;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->resident !== null
            && $invoice->resident_id === $user->resident->id;
    }
}