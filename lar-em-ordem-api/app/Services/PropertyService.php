<?php
namespace App\Services;

use App\Models\Property\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    #region Get Properties
    public function getResidentProperties(int $residentId, int $perPage = 5): LengthAwarePaginator
    {
        return Property::query()
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                      ->where('is_active',true);
            })
            ->with(['address','property_type','property_typology'])
            ->latest() // order by latest
            ->paginate($perPage);
    }

    public function getResidentPropertyById(int $propertyId,int $residentId): ?Property
    {
        return Property::query()
            ->where('id',$propertyId)
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                      ->where('is_active',true);
            })
            ->with(['address','property_type','property_typology'])
            ->firstorFail();
    }
    #endregion
    #region CRUD
    public function createResidentProperty(array $propertyData, int $residentId, array $contractData): Property
    {
        return DB::transaction(function () use ($propertyData, $contractData, $residentId) {
            $property = Property::create($propertyData);
            $property->contracts()->create([
                'resident_id'      => $residentId,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'] ?? now(),
                'end_date'         => $contractData['end_date'] ?? null,
                'is_active'        => true,
            ]);

            return $property->load(['address','property_type','property_typology']);
        });
    }
    public function updateResidentProperty(int $propertyId, int $residentId, array $data): Property
    {
        $property = $this->getResidentPropertyById($propertyId,$residentId);
        DB::transaction(function () use ($data, $property) {
            //transaction incase we add more steps
            $property->update($data);
        });
        return $property->fresh(['address','property_type','property_typology']);
    }

    public function deleteResidentProperty(int $propertyId, int $residentId): bool
    {
        $property = $this->getResidentPropertyById($propertyId,$residentId);
        return DB::transaction(function () use ($property) {
            $property->contracts()->where('is_active',true)
            ->update([
                'is_active' =>false,
                'end_date' => now()]);

            return $property->delete();
        });
    }
    #endregion
    #region Contract Methods
    public function addResidentToProperty(int $residentId,int $propertyId)
    {}
    public function removeResidentToProperty(int $residentId,int $propertyId)
    {}
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