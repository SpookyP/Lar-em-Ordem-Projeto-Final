<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\ConsumptionResource;
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
        
        $consumptions = $this->service->getConsumptions( 
            userId: $request->user()->id, 
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
    public function show(Request $request, int $consumptionId): JsonResponse
    {
        $consumption = $this->service->getConsumptionById(
            consumptionId: $consumptionId,
            userId: $request->user()->id
        );

        return ConsumptionResource::make($consumption)->response();
    }
}
