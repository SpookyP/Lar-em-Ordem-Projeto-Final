<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\AnnualBenchmarkSeriesRequest;
use App\Models\Property\Property;
use App\Services\Invoice\BenchmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AnnualBenchmarkSeriesController extends Controller
{
    public function __construct(
        protected BenchmarkService $service
    ) {
    }

    /**
     * Devolve, numa única resposta, a comparação anual geral e as séries
     * individuais de eletricidade, água e gás.
     */
    public function __invoke(AnnualBenchmarkSeriesRequest $request): JsonResponse
    {
        // only residents can see benchmarks (see PropertyPolicy)
        Gate::authorize('viewAny', Property::class);

        $property = $this->service->propertyFor($request->user()->resident, $request->validated('property_id'));

        Gate::authorize('view', $property);

        // not filtered by service: returns the overall comparison and every eligible service,
        // so the page switches between them without new requests
        return response()->json($this->service->annualCostComparison($property));
    }
}