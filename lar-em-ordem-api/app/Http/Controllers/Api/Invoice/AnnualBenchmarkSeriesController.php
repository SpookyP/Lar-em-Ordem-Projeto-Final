<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\AnnualBenchmarkSeriesRequest;
use App\Models\Property\Property;
use App\Services\Invoice\BenchmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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
        $data = $request->validated();
        $user = $request->user();

        // Apenas residentes podem consultar benchmarks (ver PropertyPolicy).
        Gate::authorize('viewAny', Property::class);

        $resident = $user->resident;

        // Obtém as propriedades ativas associadas ao residente.
        $properties = Property::query()
            ->whereHas('contracts', function ($query) use ($resident) {
                $query->where(
                    'property_contracts.resident_id',
                    $resident->id
                )->where(
                    'property_contracts.is_active',
                    true
                );
            })
            ->when(
                $data['property_id'] ?? null,
                fn ($query, $id) => $query->whereKey($id)
            )
            ->get();

        if ($properties->isEmpty()) {
            abort(
                404,
                'Não foi encontrada nenhuma propriedade associada ao residente.'
            );
        }

        // Exige a propriedade quando existem várias.
        if ($properties->count() > 1) {
            throw ValidationException::withMessages([
                'property_id' => [
                    'Indique a propriedade que pretende comparar.',
                ],
            ]);
        }

        $property = $properties->first();

        Gate::authorize('view', $property);

        // Não filtra por service: devolve Geral e todos os serviços
        // elegíveis para que a interface alterne localmente sem novos pedidos.
        $result = $this->service->annualCostComparison($property);

        return response()->json($result);
    }
}
