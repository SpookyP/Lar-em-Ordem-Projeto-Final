<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use App\Services\ServiceProvider\ServiceProviderService;

class ServiceProviderController extends Controller
{
    public function __construct(private ServiceProviderService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function listActiveProviders()
    {
        return ServiceProviderResource::collection(($this->service->listActive()));
    }

 
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceProviderRequest $request)
    {
        // $this->authorize('create', ServiceProviderModel::class);

        $fakeUser = User::first(); // remover quando houver Sanctum

        // $provider = $this->service->create($request->validated(), $request->user());
        $provider = $this->service->create($request->validated(), $fakeUser);

        return (new ServiceProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceProviderModel $serviceProvider)
    {
        return new ServiceProviderResource($this->service->loadFull($serviceProvider));
    }

 
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceProviderRequest $request, ServiceProviderModel $serviceProvider)
    {
          // $this->authorize('update', $serviceProvider);

        $updated = $this->service->update($serviceProvider, $request->validated());

        return new ServiceProviderResource($updated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceProviderModel $serviceProvider)
    {
        // $this->authorize('delete', $serviceProvider);

        $this->service->delete($serviceProvider);

        return response()->json(['message' => 'Prestador de serviços removido']);
    }
}
