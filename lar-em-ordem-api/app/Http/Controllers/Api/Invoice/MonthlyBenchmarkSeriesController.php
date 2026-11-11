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
        $data = $request->validated();
        $user = $request->user();

        // Apenas residentes podem consultar benchmarks (ver PropertyPolicy).
        Gate::authorize('viewAny', Property::class);

        $resident = $user->resident;

        $property = Property::query()
            ->whereHas('contracts', function ($query) use ($resident) {
                $query->where('property_contracts.resident_id', $resident->id)
                    ->where('property_contracts.is_active', true);
            })
            ->whereKey($data['property_id'])
            ->first();

        if (! $property) {
            abort(404, 'Não foi encontrada nenhuma propriedade associada ao residente.');
        }

        Gate::authorize('view', $property);

        $result = $this->service->monthlyCostSeries($property);

        return response()->json($result);
    }
}