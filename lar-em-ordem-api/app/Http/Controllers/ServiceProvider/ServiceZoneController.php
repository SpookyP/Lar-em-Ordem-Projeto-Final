<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ServiceZoneResource;
use App\Services\ServiceProvider\ServiceZoneService;
use App\Http\Requests\ServiceProvider\IndexServiceZoneRequest;


class ServiceZoneController extends Controller
{
    public function __construct(private ServiceZoneService $service) {}
    
   /**
    * Lists the service zones.
    * Results can be filtered with optional URL parameters (e.g. ?district=Porto&county=Matosinhos).
    *
    * @param IndexServiceZoneRequest $request HTTP request with the validated filters.
    * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Service zone collection.
    */
    public function index(IndexServiceZoneRequest $request)
    {
        return ServiceZoneResource::collection(
            $this->service->list($request->validated())
        );
    }
}
