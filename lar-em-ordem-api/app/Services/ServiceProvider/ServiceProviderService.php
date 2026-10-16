<?php

namespace App\Services\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceProviderService {

    /**
     * Devolve todos os prestadores de serviços ativos.
     *
     * @return Collection
     */
    public function listActive():Collection
    {
        return ServiceProviderModel::where('active', true)->get();
    }

    /**
     * Cria o perfil de prestador de um utilizador e atribui-lhe a role 'service_provider'.
     * Cada utilizador só pode ter um perfil de prestador (senão 422).
     *
     * @param array $data Dados validados para a criação do prestador.
     * @param User $owner Utilizador dono do perfil.
     * @return ServiceProviderModel
     */
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

    /**
     * Atualiza os dados de um prestador existente.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador a ser atualizado.
     * @param array $data Dados validados para a atualização.
     * @return ServiceProviderModel
     */
    public function update(ServiceProviderModel $serviceProvider, array $data): ServiceProviderModel
    {
        $serviceProvider->update($data);
        return $serviceProvider;
    }

    /**
     * Remove um prestador  e retira a role 'service_provider' ao utilizador.
     * Tudo numa transação.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador a ser removido.
     * @return void
     */
     public function delete(ServiceProviderModel $serviceProvider): void
    {
        DB::transaction(function () use ($serviceProvider) {
            $serviceProvider->delete();
            $serviceProvider->user->removeRole('service_provider');
        });
    }

    /**
     * Carrega as zonas e as especialidades (com categoria e especialidade do catálogo) de um prestador.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @return ServiceProviderModel A mesma instância, com as relações carregadas.
     */
   public function loadFull(ServiceProviderModel $provider): ServiceProviderModel
    {
        return $provider->load(['zones', 'specialties.category', 'specialties.specialty']);
    }

     /**
     * Define as zonas onde o prestador atua. A lista recebida substitui a anterior.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array<int> $zoneIds IDs das zonas.
     * @return ServiceProviderModel A mesma instância, com a relação 'zones' carregada.
     */
    public function syncZones(ServiceProviderModel $provider, array $zoneIds): ServiceProviderModel
    {
        $provider->zones()->sync($zoneIds);

        return $provider->load('zones');
    }

    /**
     * Devolve o perfil de prestador de um utilizador; termina com 403 se não existir.
     *
     * @param User $user Utilizador a procurar.
     * @return ServiceProviderModel
     */
    public function getByUser(User $user): ServiceProviderModel
    {
        return ServiceProviderModel::where('user_id', $user->id)->first()
            ?? abort(403, 'Este utilizador não tem um perfil de prestador de serviços.');
    }
}