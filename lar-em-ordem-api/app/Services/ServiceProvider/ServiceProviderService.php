<?php

namespace App\Services\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ServiceProviderService {

    public function listActive():Collection
    {
        return ServiceProviderModel::where('active', true)->get();
    }

    public function create(array $data, User $owner): ServiceProviderModel
    {
        if(ServiceProviderModel::where('user_id', $owner->id)->exists())
            {
                throw ValidationException::withMessages([
                    'user_id'=> 'Este utilizador já tem um perfil de prestador de serviços.'
                ]);
            }
        
            return ServiceProviderModel::create($data+[
                'user_id'=> $owner->id
            ]);
    }

    public function update(ServiceProviderModel $serviceProvider, array $data): ServiceProviderModel
    {
        $serviceProvider->update($data);
        return $serviceProvider;
    }

    public function delete (ServiceProviderModel $serviceProvider): void
    {
        $serviceProvider->delete();
    }

     public function loadFull(ServiceProviderModel $provider): ServiceProviderModel
    {
        // return $provider->load(['specialties.category', 'specialties.specialty', 'zones']);
        return $provider;
    }
}