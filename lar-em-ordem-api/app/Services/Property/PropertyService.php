<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use App\Services\Resident\ResidentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    public function __construct(protected ResidentService $residentService) {}
    #region Metodos Privados
    private function _userIdtoResidentId(int $userId): int
    {
        return $this->residentService->getResidentByUserId($userId)->id;
    }
    #endregion
    #region CRUD
    #region Get Properties
    public function getResidentProperties(int $userId, int $perPage = 5): LengthAwarePaginator
    {
        $residentId = $this->_userIdtoResidentId($userId);
        return Property::query()
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                    ->where('is_active', true);
            })
            ->with(['address', 'property_type', 'property_typology'])
            ->latest() // order by latest
            ->paginate($perPage);
    }

    public function getResidentPropertyById(int $propertyId, int $userId): ?Property
    {
        $residentId = $this->_userIdtoResidentId($userId);
        $property = Property::query()
            ->where('id', $propertyId)
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                    ->where('is_active', true);
            })
            ->with(['address', 'property_type', 'property_typology'])
            ->first();

        if (!$property) {
            abort(404, 'Property not found under the resident current properties');
        }
        return $property;
    }
    #endregion
    public function createResidentProperty(array $propertyData, int $userId, array $contractData): Property
    {
        $residentId = $this->_userIdtoResidentId($userId);
        return DB::transaction(function () use ($propertyData, $contractData, $residentId) {
            $property = Property::create($propertyData);
            $property->contracts()->create([
                'resident_id'      => $residentId,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'] ?? now(),
                'end_date'         => $contractData['end_date'] ?? null,
                'is_active'        => true,
            ]);

            return $property->load(['address', 'property_type', 'property_typology']);
        });
    }
    public function updateResidentProperty(int $propertyId, int $userId, array $data): Property
    {
        $residentId = $this->_userIdtoResidentId($userId);
        $property = $this->getResidentPropertyById($propertyId, $residentId);
        return DB::transaction(function () use ($data, $property) {
            $property->update($data);
            return $property->fresh(['address', 'property_type', 'property_typology']);
        });
    }

    public function deleteResidentProperty(int $propertyId, int $userId): bool
    {
        $residentId = $this->_userIdtoResidentId($userId);
        $property = $this->getResidentPropertyById($propertyId, $residentId);
        return DB::transaction(function () use ($property) {
            $property->contracts()->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'end_date' => now()
                ]);
            return $property->delete();
        });
    }
    #endregion
    #region Contract Methods
    public function addResidentToProperty(int $residentId, int $propertyId) {}
    public function removeResidentToProperty(int $residentId, int $propertyId) {}
    public function terminateContract(int $contractId): bool
    {
        DB::transaction(function () use ($contractId) {
            //$contract =getContractById->where('is_active'=>true)
            //$contract->update(['is_active'=>false,'end_date'=>now()])
        });
        return false;
    }
    #endregion
}
