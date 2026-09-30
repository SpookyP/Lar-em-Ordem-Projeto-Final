<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use App\Models\Property\Address;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\Property\PropertyContract;
use App\Models\User\ResidentType;
use App\Services\Resident\ResidentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
    #region Get Types
    public function getPropertyForms(): array
    {
        return Cache::remember('properties.form_options', now()->addDay(), function () {
            return [
                'propertyTypes' => PropertyType::select('id', 'type')->get(),
                'propertyTypologies' => PropertyTypology::select('id', 'typology')->get(),
                'residentTypes' => ResidentType::select('id', 'type')->get(),
            ];
        });
    }
    #endregion

    #region Get Properties
    public function getResidentProperties(int $userId, int $perPage = 5): LengthAwarePaginator
    {
        $residentId = $this->_userIdtoResidentId($userId);

        return Property::query()
            ->whereHas('contracts', function ($query) use ($residentId) {
                $query->where('resident_id', $residentId)
                    ->where('is_active', true);
            })
            ->with([
                'address:id,street,location',
                'propertyType:id,type',
                'propertyTypology:id,typology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType:id,type'),
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
            ->with([
                'address',
                'propertyType',
                'propertyTypology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType'),
            ])
            ->first();

        if (!$property) {
            abort(404, 'Property not found under the resident current properties');
        }
        return $property;
    }

    #endregion
    public function createResidentProperty(int $userId, array $propertyData, array $contractData, array $addressData): Property
    {
        $residentId = $this->_userIdtoResidentId($userId);
        return DB::transaction(function () use ($propertyData, $contractData, $addressData, $residentId,) {
            $address = Address::create($addressData);
            $propertyData['address_id'] = $address->id;
            $property = Property::create($propertyData);
            $property->contracts()->create([
                'resident_id'      => $residentId,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'] ?? now(),
                'end_date'         => $contractData['end_date'] ?? null,
                'is_active'        => true,
            ]);

            return $property->load([
                'address',
                'propertyType',
                'propertyTypology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType'),
            ]);
        });
    }

    public function updateResidentProperty(int $propertyId, int $userId, array $propertyData, array $contractData, array $addressData): Property
    {
        $property = $this->getResidentPropertyById($propertyId, $userId);
        return DB::transaction(function () use ($propertyData, $contractData, $addressData, $property,) {

            if (!empty($addressData)) {
                $property->address->update($addressData);
            }

            if (!empty($propertyData)) {
                $property->update($propertyData);
            }

            if (!empty($contractData)) {
                $property->contracts()
                    ->where('is_active', true)
                    ->first()
                    ?->update($contractData);
            }


            return $property->fresh([
                'address',
                'propertyType',
                'propertyTypology',
                'contracts' => fn($query) => $query->where('is_active', true)->with('residentType'),
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

    public function terminateContract(int $propertyId, int $userId): bool
    {
        $residentId = $this->_userIdtoResidentId($userId);
        return DB::transaction(function () use ($propertyId, $residentId) {
            $contract = PropertyContract::query()
                ->where('property_id', $propertyId)
                ->where('resident_id', $residentId)
                ->where('is_active', true)
                ->first();
            if (!$contract) {
                return false;
            }
            $contract->update([
                'is_active' => false,
                'end_date' => now()
            ]);
            return $contract->delete();
        });
    }
    #endregion
}
