<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Resources\Invoice\ConsumptionResource;
use App\Http\Resources\Invoice\ConsumptionItemResource;
use App\Models\Invoice\Consumption;
use App\Services\Invoice\ConsumptionService;
use Illuminate\Support\Facades\Gate;
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        Gate::authorize('viewAny', Consumption::class);

        $resident = $request->user()->resident;

        $consumptions = $this->service->getConsumptions(
            residentId: $resident->id,
            propertyId: $validated['property_id'] ?? null,
            perPage: $validated['per_page'] ?? 15
        );

        return ConsumptionResource::collection($consumptions);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $consumptionId): JsonResponse
    {
        Gate::authorize('viewAny', Consumption::class);

        $resident = $request->user()->resident;

        $consumption = $this->service->getConsumptionById(
            consumptionId: $consumptionId,
            residentId: $resident->id
        );

        Gate::authorize('view', $consumption);

        return ConsumptionResource::make($consumption)->response();
    }

    /**
     * Display the items of a specific consumption.
     */
    public function items(Request $request, string $consumptionId): JsonResponse
    {
        Gate::authorize('viewAny', Consumption::class);

        $resident = $request->user()->resident;

        $consumption = $this->service->getConsumptionById(
            consumptionId: $consumptionId,
            residentId: $resident->id
        );

        Gate::authorize('view', $consumption);

        return ConsumptionItemResource::collection($consumption->items)->response();
    }
}