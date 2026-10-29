<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\ConsumptionResource;
use App\Http\Resources\Invoice\ConsumptionItemResource;
use App\Services\Invoice\ConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsumptionController extends Controller
{
    public function __construct(private ConsumptionService $service)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) 
    { 
        $validated = $request->validate([ 
            'property_id' => ['nullable', 'string', 'exists:properties,id'],
        ]);
        
        $resident = $request->user()->resident;
        
        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $consumptions = $this->service->getConsumptions( 
            residentId: $resident->id, 
            propertyId: $validated['property_id'] ?? null 
        );

        if ($consumptions->currentPage() > $consumptions->lastPage() && $consumptions->lastPage() > 0)
        {
            abort(404, 'Page out of bounds.'); 
        }
        
        return ConsumptionResource::collection($consumptions); 
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $consumptionId): JsonResponse
    {
        $resident = $request->user()->resident;
        
        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $consumption = $this->service->getConsumptionById(
            consumptionId: $consumptionId,
            residentId: $resident->id
        );

        return ConsumptionResource::make($consumption)->response();
    }

    /**
     * Display the items of a specific consumption.
     */
    public function items(Request $request, string $consumptionId): JsonResponse
    {
        $resident = $request->user()->resident;
        
        if (! $resident) {
            abort(404, 'Resident profile not found.');
        }

        $consumption = $this->service->getConsumptionById(
            consumptionId: $consumptionId,
            residentId: $resident->id
        );

        return ConsumptionItemResource::collection($consumption->items)->response();
    }
}