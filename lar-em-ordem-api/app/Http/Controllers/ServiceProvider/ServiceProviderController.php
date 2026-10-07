<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceProvider\UpdateServiceProviderRequest;
use App\Http\Resources\ServiceProvider\ServiceProviderResource;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Services\ServiceProvider\ServiceProviderService;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\ServiceProvider\StoreServiceProviderRequest;

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
     * Display the specified resource.
     */
    public function show(ServiceProviderModel $serviceProvider)
    {
        return new ServiceProviderResource($this->service->loadFull($serviceProvider));
    }

    public function store(StoreServiceProviderRequest $request)
    {
        Gate::authorize('create', ServiceProviderModel::class);

        $provider = $this->service->create($request->validated(), $request->user());

        return (new ServiceProviderResource($provider))
            ->response()
            ->setStatusCode(201);
    }
 
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceProviderRequest $request, ServiceProviderModel $serviceProvider)
    {
        Gate::authorize('update', $serviceProvider);

        $updated = $this->service->update($serviceProvider, $request->validated());

        return new ServiceProviderResource($updated);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceProviderModel $serviceProvider)
    {
        Gate::authorize('delete', $serviceProvider);

        $this->service->delete($serviceProvider);

        return response()->json(['message' => 'Prestador de serviços removido']);
    }
}
