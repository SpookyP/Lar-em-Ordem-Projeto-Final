<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function __construct(private ServiceProviderService $service) {}

     /**
     * Lista todos os prestadores de serviços.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de prestadores.
     */
    public function index()
    {
        return ServiceProviderResource::collection($this->service->list());
    }

    /**
     * Devolve os detalhes de um prestador específico, incluindo as suas zonas e especialidades.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @return ServiceProviderResource
     */
    public function show(ServiceProviderModel $serviceProvider)
    {
        return new ServiceProviderResource($this->service->loadFull($serviceProvider));
    }

    /**
     * Devolve o perfil de prestador do utilizador autenticado, incluindo as suas zonas e especialidades.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @return ServiceProviderResource
     */
    public function showMyProfile(Request $request)
    {
        $provider = $this->service->getByUser($request->user());

        return new ServiceProviderResource($this->service->loadFull($provider));
    }

    /**
     * Cria o perfil de prestador do utilizador autenticado e atribui-lhe a role 'service_provider'.
     * Pode receber logo as zonas (zone_ids) e as especialidades (service_specialty_ids, custom_specialties).
     *
     * @param StoreServiceProviderRequest $request Dados validados.
     * @return \Illuminate\Http\JsonResponse Devolve o recurso criado com o status 201 (Created).
     */
    public function store(StoreServiceProviderRequest $request)
    {
        Gate::authorize('create', ServiceProviderModel::class);

        $provider = $this->service->create($request->validated(), $request->user());

        return (new ServiceProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }
 
    /**
     * Atualiza os dados de um prestador existente, incluindo as zonas e as especialidades.
     * Cada lista enviada substitui a anterior; as que não forem enviadas ficam iguais.
     *
     * @param UpdateServiceProviderRequest $request Dados validados.
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @return ServiceProviderResource Devolve o prestador atualizado.
     */
    public function update(UpdateServiceProviderRequest $request, ServiceProviderModel $serviceProvider)
    {
        Gate::authorize('update', $serviceProvider);

        $updated = $this->service->update($serviceProvider, $request->validated());

        return new ServiceProviderResource($updated);
    }

    /**
     * Remove um prestador e retira a role 'service_provider' ao utilizador.
     * Só o dono do perfil ou o SU podem remover.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador.
     * @return \Illuminate\Http\JsonResponse Confirmação da remoção.
     */
    public function destroy(ServiceProviderModel $serviceProvider)
    {
        Gate::authorize('delete', $serviceProvider);

        $this->service->delete($serviceProvider);

        return response()->json(['message' => 'Prestador de serviços removido']);
    }

}
