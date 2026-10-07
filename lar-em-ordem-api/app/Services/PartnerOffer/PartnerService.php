<?php


namespace App\Services\PartnerOffer;

use App\Models\User\Partner;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerService
{
    public function findByUser(User $user): ?Partner
    {
        return Partner::where('user_id', $user->id)->first();
    }

    public function getByUser(User $user): Partner
    {
        return $this->findByUser($user)
            ?? abort(403, 'Este utilizador não tem um perfil de parceiro.');
    }
    
    public function listActive(): Collection 
    {
        return Partner::where('active', true)->get();
    }

    public function create(array $data, User $owner): Partner
    {
        if ($this->findByUser($owner)) {
            throw ValidationException::withMessages([
                'user_id' => 'Este utilizador já tem um perfil de parceiro.',
            ]);
        }
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('partner');

            return Partner::create($data + [
                'user_id' => $owner->id,
            ]);
        });
    }

    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);
        return $partner;
    }

   public function delete(Partner $partner): void
    {
        DB::transaction(function () use ($partner) {
            $partner->offers()->delete();
            $partner->delete();
            $partner->user->removeRole('partner');
        });
    }

    public function loadWithOffers(Partner $partner): Partner
    {
        return $partner->load('offers');
    }
}