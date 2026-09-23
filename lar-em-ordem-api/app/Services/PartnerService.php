<?php


namespace App\Services;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PartnerService
{
    public function listActive(): Collection 
    {
        return Partner::where('active', true)->get();
    }

    public function create(array $data, User $owner): Partner
    {
        return Partner::create($data + [
            'user_id' => $owner->id,
            'active'  => true,
        ]);
    }

    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);
        return $partner;
    }

    public function delete(Partner $partner): void
    {
        $partner->delete();
    }

    public function loadWithOffers(Partner $partner): Partner
    {
        return $partner->load('offers');
    }
}