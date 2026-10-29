<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Services\ServiceProvider\ServiceProviderService;
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
}
