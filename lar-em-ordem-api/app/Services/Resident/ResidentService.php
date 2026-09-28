<?php
namespace App\Services\Resident;

use App\Models\User\Resident;
use Illuminate\Support\Facades\DB;

class ResidentService
{
    #region CRUD
    #region Get Resident
    public function getResidentByUserId(int $userId){
        $resident = Resident::query()
            ->where('user_id',$userId)
            ->first();

        if(!$resident){
            abort(404, 'Resident not found under the user profile.');
        }
        return $resident;
    }
    #endregion
    public function updateResident(int $userId, array $data): Resident
    {
        $resident = $this->getResidentByUserId($userId);
        return DB::transaction(function () use ($data, $resident) {
            $resident->update($data);
            return $resident->fresh();
        });
    }
    #endregion
}