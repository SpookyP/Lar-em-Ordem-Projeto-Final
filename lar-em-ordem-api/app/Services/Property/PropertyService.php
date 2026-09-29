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
            ->with(['address', 'property_type', 'property_typology',
            'contracts' => fn ($query) =>$query->where('is_active',true)->with('resident_type'),
            ])
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
            ->with(['address', 'property_type', 'property_typology',
            'contracts' => fn ($query) => $query->where('is_active', true)->with('resident_type'),
            ])
            ->first();

        if (!$property) {
            abort(404, 'Property not found under the resident current properties');
        }
        return $property;
    }
    #endregion
    public function createResidentProperty(int $userId, array $propertyData, array $contractData): Property
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

            return $property->load(['address', 'property_type', 'property_typology',
            'contracts' => fn ($query) => $query->where('is_active', true)->with('resident_type'),
            ]);
        });
    }
    public function updateResidentProperty(int $propertyId, int $userId, array $data): Property
    {
        $property = $this->getResidentPropertyById($propertyId, $userId);
        return DB::transaction(function () use ($data, $property) {
            $property->update($data);
            return $property->fresh(['address', 'property_type', 'property_typology',
            'contracts' => fn ($query) => $query->where('is_active', true)->with('resident_type'),
            ]);
        });
    }

    public function deleteResidentProperty(int $propertyId, int $userId): bool
    {
        $property = $this->getResidentPropertyById($propertyId, $userId);
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
}
