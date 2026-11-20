<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\MonthlyBenchmarkSeriesRequest;
use App\Models\Property\Property;
use App\Services\Invoice\BenchmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MonthlyBenchmarkSeriesController extends Controller
{
    public function __construct(
        protected BenchmarkService $service
    ) {
    }

    public function __invoke(MonthlyBenchmarkSeriesRequest $request): JsonResponse
    {
        // only residents can see benchmarks (see PropertyPolicy)
        Gate::authorize('viewAny', Property::class);

        $property = $this->service->propertyFor($request->user()->resident, $request->validated('property_id'));

        Gate::authorize('view', $property);

        return response()->json($this->service->monthlyCostSeries($property));
    }
}