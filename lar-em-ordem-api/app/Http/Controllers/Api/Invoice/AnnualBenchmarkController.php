<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\AnnualBenchmarkRequest;
use App\Models\Property\Property;
use App\Services\Invoice\BenchmarkService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AnnualBenchmarkController extends Controller
{
    public function __construct(protected BenchmarkService $service)
    {
    }

    /**
     * Processa o pedido de benchmark anual.
     */
    public function __invoke(AnnualBenchmarkRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $resident = $user->resident;

        if (! $resident) {
            abort(404, 'Perfil de residente não encontrado.');
        }

        Gate::authorize('viewAny', Property::class);

        // Obtém as propriedades ativas associadas ao residente.
        $properties = Property::query()
            ->whereHas('contracts', function ($query) use ($resident) {
                $query->where('property_contracts.resident_id', $resident->id)
                    ->where('property_contracts.is_active', true);
            })
            ->when(
                $data['property_id'] ?? null,
                fn ($q, $id) => $q->whereKey($id)
            )
            ->get();

        if ($properties->isEmpty()) {
            abort(404, 'Não foi encontrada nenhuma propriedade associada ao residente.');
        }

        // Exige a identificação da propriedade quando existem várias.
        if ($properties->count() > 1) {
            throw ValidationException::withMessages([
                'property_id' => [
                    'Indique a propriedade que pretende comparar.',
                ],
            ]);
        }

        $property = $properties->first();

        Gate::authorize('view', $property);

        // Define a data de início para o ano solicitado.
        $start = Carbon::createFromFormat('!Y', (string) $data['year'])->startOfYear();

        // Calcula o benchmark anual.
        $result = $this->service->comparePeriod(
            property: $property,
            period: 'annual',
            consumptionTypeId: (int) $data['consumption_type_id'],
            periodStart: $start
        );

        return response()->json($result);
    }
}