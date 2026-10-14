<?php
namespace App\Services\Resident;

use App\Models\User\Resident;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class ResidentService
{
    #region CRUD
    #region Get Resident
    public function getResidentByUserId(Ulid $userId){
        $resident = Resident::query()
            ->where('user_id',$userId)
            ->first();

        if(!$resident){
            abort(404, 'Resident not found under the user profile.');
        }
        return $resident;
    }
    #endregion
    public function updateResident(string|Ulid $userId, array $data): Resident
    {
        $ulid = is_string($userId) ? new Ulid($userId) : $userId;
        $resident = $this->getResidentByUserId($ulid);
        return DB::transaction(function () use ($data, $resident) {
            $resident->update($data);
            return $resident->fresh();
        });
    }
    #endregion
}