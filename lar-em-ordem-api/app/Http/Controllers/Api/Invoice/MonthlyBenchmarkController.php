<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\MonthlyBenchmarkRequest;
use App\Services\Invoice\BenchmarkService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MonthlyBenchmarkController extends Controller
{
    public function __construct(protected BenchmarkService $service) {}

    // Processa a requisição do endpoint de benchmark mensal
    public function __invoke(MonthlyBenchmarkRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // Valida se o utilizador autenticado possui perfil de residente
        $resident = \App\Models\User\Resident::where('user_id', $user->id)->first();
        if (!$resident) {
            abort(404, 'Resident not found.');
        }

        // Obtém as propriedades ativas associadas ao residente
        $properties = \App\Models\Property\Property::query()
            ->whereHas('contracts', function ($query) use ($resident) {
                $query->where('property_contracts.resident_id', $resident->id)
                      ->where('property_contracts.is_active', true);
            })
            ->when($data['property_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->get();

        if ($properties->isEmpty()) {
            abort(404, 'Property not found.');
        }

        // Garante que é especificada apenas uma propriedade caso existam várias
        if ($properties->count() > 1) {
            throw ValidationException::withMessages([
                'property_id' => ['Please specify which property to compare.'],
            ]);
        }

        // Define a data de início para o mês solicitado
        $start = Carbon::createFromFormat('!Y-m', $data['month'])->startOfMonth();

        // Atribui o cálculo do benchmark mensal ao serviço de suporte
        $result = $this->service->comparePeriod(
            property: $properties->first(),
            period: 'monthly',
            consumptionTypeId: (int) $data['consumption_type_id'],
            periodStart: $start
        );

        return response()->json($result);
    }
}
