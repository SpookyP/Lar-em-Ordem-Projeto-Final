<?php

namespace App\Services\Contract;

use App\Models\Property\PropertyContract;
use App\Services\Resident\ResidentService;
use App\Services\Property\PropertyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        protected ResidentService $residentService,
        protected PropertyService $propertyService)
    {}
    #region Metodos Privados
    #endregion
    #region CRUD
    #region Get Properties
    #endregion
    public function createContract(int $userId, int $propertyId, array $contractData): PropertyContract
    {
        $this->propertyService->getResidentPropertyById($propertyId, $userId);
        
        $residentId = $this->residentService->getResidentByUserId($userId)->id;

        return DB::transaction(function () use ($propertyId, $residentId, $contractData) {
            $endDate = $contractData['end_date'] ?? null;
            $isActive = $endDate === null || $endDate >= date('Y-m-d');

            $contract = PropertyContract::create([
                'property_id'      => $propertyId,
                'resident_id'      => $residentId,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'],
                'end_date'         => $endDate,
                'is_active'        => $isActive,
            ]);

            return $contract->load(['resident_type']);
        });
    }

    /**
     * Add a resident to a property by creating a new contract.
     */
    public function addResidentToProperty(int $residentId, int $propertyId, array $contractData): PropertyContract
    {
        return DB::transaction(function () use ($residentId, $propertyId, $contractData) {
            $endDate = $contractData['end_date'] ?? null;
            $isActive = $endDate === null || $endDate >= date('Y-m-d');

            return PropertyContract::create([
                'property_id'      => $propertyId,
                'resident_id'      => $residentId,
                'resident_type_id' => $contractData['resident_type_id'],
                'start_date'       => $contractData['start_date'] ?? date('Y-m-d'),
                'end_date'         => $endDate,
                'is_active'        => $isActive,
            ]);
        });
    }

    /**
     * Remove a resident from a property by deactivating their active contract(s).
     */
    public function removeResidentFromProperty(int $residentId, int $propertyId): bool
    {
        return DB::transaction(function () use ($residentId, $propertyId) {
            $affectedRows = PropertyContract::query()
                ->where('property_id', $propertyId)
                ->where('resident_id', $residentId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'end_date'   => date('Y-m-d'),
                ]);

            return $affectedRows > 0;
        });
    }

    /**
     * Terminate a specific contract by its primary key ID.
     */
    public function terminateContract(int $contractId): bool
    {
        return DB::transaction(function () use ($contractId) {
            $contract = PropertyContract::query()
                ->where('id', $contractId)
                ->where('is_active', true)
                ->first();

            if (!$contract) {
                return false;
            }

            return $contract->update([
                'is_active' => false,
                'end_date'   => date('Y-m-d'),
            ]);
        });
    }
    #endregion
}
