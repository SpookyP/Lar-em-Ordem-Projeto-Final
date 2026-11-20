<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Services\Auth\ProfileService;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function __construct(
        private ServiceProviderService $service,
        private ProfileService $profiles,
    ) {}

    /**
     * Devolve o perfil de prestador do utilizador autenticado, incluindo as suas zonas e especialidades.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @return ServiceProviderResource
     */
    public function showMyProfile(Request $request)
    {
        $provider = $this->profiles->get($request->user(), 'service_provider');

        return new ServiceProviderResource($this->service->loadFull($provider));
    }
}