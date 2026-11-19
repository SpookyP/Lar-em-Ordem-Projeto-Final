<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerPageRequest;
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
    public function index(PerPageRequest $request)
    {
        // Validar os filtros específicos dos consumos.
        $validated = $request->validate([
            'property_id' => ['nullable', 'string', 'exists:properties,id'],
        ]);

        Gate::authorize('viewAny', Consumption::class);

        $resident = $request->user()->resident;

        // Obter os consumos com os filtros e a paginação aplicados.
        $consumptions = $this->service->getConsumptions(
            residentId: $resident->id,
            propertyId: $validated['property_id'] ?? null,
            perPage: $request->validated('per_page') ?? 5
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
