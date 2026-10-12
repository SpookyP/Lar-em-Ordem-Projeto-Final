<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\StoreProviderSpecialtyRequest;
use App\Http\Resources\ServiceProvider\ProviderSpecialtyResource;
use App\Models\ServiceProvider\ProviderSpecialty;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Services\ServiceProvider\ProviderSpecialtyService;
use Illuminate\Support\Facades\Gate;

class ProviderSpecialtyController extends Controller
{
    public function __construct(private ProviderSpecialtyService $service) {}

    /**
     * Lista as especialidades de um prestador.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de especialidades.
     */
    public function index(ServiceProviderModel $serviceProvider)
    {
        return ProviderSpecialtyResource::collection($this->service->list($serviceProvider));
    }

    /**
     * Acrescenta especialidades ao prestador: uma ou várias da lista, ou uma própria (custom).
     *
     * @param StoreProviderSpecialtyRequest $request Dados validados.
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @return \Illuminate\Http\JsonResponse Coleção das especialidades criadas, com o status 201 (Created).
     */
       public function store(StoreProviderSpecialtyRequest $request, ServiceProviderModel $serviceProvider)
    {
        Gate::authorize('update', $serviceProvider);

        $created = $this->service->add($serviceProvider, $request->validated());

        return ProviderSpecialtyResource::collection($created)
            ->response()
            ->setStatusCode(201);
    }

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