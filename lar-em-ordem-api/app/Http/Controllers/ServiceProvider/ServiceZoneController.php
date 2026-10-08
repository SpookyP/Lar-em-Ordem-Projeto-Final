<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceZoneResource;
use App\Models\ServiceProvider\ServiceZone;
use App\Services\ServiceProvider\ServiceZoneService;
use App\Http\Requests\ServiceProvider\IndexServiceZoneRequest;


class ServiceZoneController extends Controller
{
    public function __construct(private ServiceZoneService $service) {}
    
   /**
     * Lista as zonas de serviço.
     * Permite filtrar os resultados através dos parâmetros opcionais no URL (ex: ?district=Porto&county=Matosinhos).
     *
     * @param IndexServiceZoneRequest $request O pedido HTTP com os dados de filtragem validados.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de zonas de serviço.
     */
    public function index(IndexServiceZoneRequest $request)
    {
        return ServiceZoneResource::collection(
            $this->service->list($request->validated())
        );
    }

  /**
     * Devolve os detalhes de uma zona de serviço específica.
     *
     * @param ServiceZone $serviceZone Instância da zona de serviço.
     * @return ServiceZoneResource
     */
    public function show(ServiceZone $serviceZone)
    {
        return new ServiceZoneResource($serviceZone);
    }
}
