<?php

namespace App\Http\Controllers\Api\Property;

use Illuminate\Http\JsonResponse;
use App\Http\Resources\Contract\PropertyContractResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StorePropertyContractRequest;
use App\Http\Requests\Contract\UpdatePropertyContractRequest;
use App\Services\Contract\ContractService;

class PropertyContractController extends Controller
{
    public function __construct(protected ContractService $service) {}

    /**
     * Store a newly created contract for a specific property.
     */
    public function store(StorePropertyContractRequest $request, int $propertyId): JsonResponse
    {
        $contract = $this->service->createContract(
            userId: $request->user()->id,
            propertyId: $propertyId,
            contractData: $request->contractData()
        );

        return PropertyContractResource::make($contract)
            ->additional(['message' => 'Contract created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function addResident(
        StorePropertyContractRequest $request, 
        int $propertyId, 
        int $residentId
    ): JsonResponse {
        $contract = $this->service->addResidentToProperty(
            residentId: $residentId,
            propertyId: $propertyId,
            contractData: $request->validated()
        );

        return PropertyContractResource::make($contract)
            ->additional(['message' => 'Resident added to property successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a resident from a property (deactivates active contract).
     * DELETE /api/properties/{property}/residents/{resident}
     */
    public function removeResident(int $propertyId, int $residentId): JsonResponse
    {
        $removed = $this->service->removeResidentFromProperty(
            residentId: $residentId,
            propertyId: $propertyId
        );

        if (!$removed) {
            return response()->json([
                'message' => 'No active contract found for this resident on this property.'
            ], 404);
        }

        return response()->json([
            'message' => 'Resident removed from property successfully.'
        ]);
    }

    /**
     * Terminate a specific contract by its primary key ID.
     * PATCH /api/contracts/{contract}/terminate
     */
    public function terminate(int $contractId): JsonResponse
    {
        $terminated = $this->service->terminateContract($contractId);

        if (!$terminated) {
            return response()->json([
                'message' => 'Contract not found or already inactive.'
            ], 404);
        }

        return response()->json([
            'message' => 'Contract terminated successfully.'
        ]);
    }
}
