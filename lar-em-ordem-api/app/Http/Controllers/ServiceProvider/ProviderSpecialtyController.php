<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Models\ServiceProvider\ProviderSpecialty;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Services\ServiceProvider\ProviderSpecialtyService;
use Illuminate\Support\Facades\Gate;

class ProviderSpecialtyController extends Controller
{
    public function __construct(private ProviderSpecialtyService $service) {}

    /**
     * Remove uma especialidade do prestador.
     * A rota usa scopeBindings: a especialidade tem de pertencer a este prestador (senão 404).
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @param ProviderSpecialty $specialty Especialidade a remover.
     * @return \Illuminate\Http\JsonResponse Confirmação da remoção.
     */
    public function destroy(ServiceProviderModel $serviceProvider, ProviderSpecialty $specialty)
    {
        Gate::authorize('update', $serviceProvider);

        $this->service->remove($specialty);

        return response()->json(['message' => 'Especialidade removida.']);
    }
}