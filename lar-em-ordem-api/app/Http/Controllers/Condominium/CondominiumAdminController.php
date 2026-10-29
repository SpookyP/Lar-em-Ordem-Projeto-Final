<?php

namespace App\Http\Controllers\Condominium;

use App\Http\Controllers\Controller;
use App\Http\Resources\Condominium\CondominiumAdminResource;
use App\Services\Condominium\CondominiumAdminService;
use Illuminate\Http\Request;

class CondominiumAdminController extends Controller
{
    public function __construct(private CondominiumAdminService $service) {}

    /**
     * Devolve o perfil de administrador de condomínio do utilizador autenticado.
     *
     * @param Request $request Pedido com o utilizador autenticado.
     * @return CondominiumAdminResource
     */
    public function showMyProfile(Request $request): CondominiumAdminResource
    {
        return new CondominiumAdminResource($this->service->getByUser($request->user()));
    }
}