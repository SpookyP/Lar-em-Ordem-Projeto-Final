<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function __construct(private ServiceProviderService $service) {}

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