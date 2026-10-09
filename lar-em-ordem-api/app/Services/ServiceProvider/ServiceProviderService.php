<?php

namespace App\Services\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceProviderService {

    public function listActive():Collection
    {
        return ServiceProviderModel::where('active', true)->get();
    }

    public function create(array $data, User $owner): ServiceProviderModel
    {
        if (ServiceProviderModel::where('user_id', $owner->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Este utilizador já tem um perfil de prestador de serviços.',
            ]);
        }
        
        return DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('service_provider');

            return ServiceProviderModel::create($data + [
                'user_id' => $owner->id,
            ]);
        });
    }
    
    public function update(ServiceProviderModel $serviceProvider, array $data): ServiceProviderModel
    {
        $serviceProvider->update($data);
        return $serviceProvider;
    }

     public function delete(ServiceProviderModel $serviceProvider): void
    {
        DB::transaction(function () use ($serviceProvider) {
            $serviceProvider->delete();
            $serviceProvider->user->removeRole('service_provider');
        });
    }

     public function loadFull(ServiceProviderModel $provider): ServiceProviderModel
    {
        // return $provider->load(['specialties.category', 'specialties.specialty', 'zones']);
        return $provider->load('zones');
    }

    /**
     * Define as zonas onde o prestador atua. A lista recebida substitui a anterior.
     *
     * @param ServiceProviderModel $provider
     * @param array<int> $zoneIds IDs das zonas
     */
    public function syncZones(ServiceProviderModel $provider, array $zoneIds): ServiceProviderModel
    {
        $provider->zones()->sync($zoneIds);

        return $provider->load('zones');
    }
}