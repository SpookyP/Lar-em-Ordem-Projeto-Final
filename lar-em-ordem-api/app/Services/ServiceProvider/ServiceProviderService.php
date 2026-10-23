<?php

namespace App\Services\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceProviderService {

    /** Campos do pedido que não são colunas do prestador (são relações). */
    private const RELATION_KEYS = ['zone_ids', 'service_specialty_ids', 'custom_specialties'];

    public function __construct(private ProviderSpecialtyService $specialtyService) {}

    /**
     * Devolve todos os prestadores de serviços (os apagados ficam de fora pelo soft delete).
     *
     * @return Collection
     */
    public function list(): Collection
    {
        return ServiceProviderModel::all();
    }

    /**
     * Cria o perfil de prestador de um utilizador e atribui-lhe a role 'service_provider'.
     * Pode receber logo as zonas e as especialidades (da lista e próprias).
     * Cada utilizador só pode ter um perfil de prestador (senão 422). Transação.
     *
     * @param array $data Dados validados (inclui opcionalmente zone_ids, service_specialty_ids, custom_specialties).
     * @param User $owner Utilizador dono do perfil.
     * @return ServiceProviderModel O prestador com zonas e especialidades carregadas.
     */
    public function create(array $data, User $owner): ServiceProviderModel
    {
        if (ServiceProviderModel::where('user_id', $owner->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Este utilizador já tem um perfil de prestador de serviços.',
            ]);
        }

        $provider = DB::transaction(function () use ($data, $owner) {
            $owner->assignRole('service_provider');

            $provider = ServiceProviderModel::create(Arr::except($data, self::RELATION_KEYS) + [
                'user_id' => $owner->id,
            ]);

            $this->syncRelations($provider, $data);

            return $provider;
        });

        return $this->loadFull($provider);
    }

    /**
     * Atualiza os dados de um prestador e, se enviadas, as zonas e as especialidades.
     * Cada lista enviada substitui a anterior; as que não forem enviadas ficam iguais. Transação.
     *
     * @param ServiceProviderModel $serviceProvider Instância do prestador a ser atualizado.
     * @param array $data Dados validados para a atualização.
     * @return ServiceProviderModel O prestador com zonas e especialidades carregadas.
     */
    public function update(ServiceProviderModel $serviceProvider, array $data): ServiceProviderModel
    {
        DB::transaction(function () use ($serviceProvider, $data) {
            $serviceProvider->update(Arr::except($data, self::RELATION_KEYS));

            $this->syncRelations($serviceProvider, $data);
        });

        return $this->loadFull($serviceProvider);
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
     * Substitui as zonas e/ou as especialidades do prestador, só as listas que vierem nos dados.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array $data Dados validados.
     * @return void
     */
    private function syncRelations(ServiceProviderModel $provider, array $data): void
    {
        if (array_key_exists('zone_ids', $data)) {
            $provider->zones()->sync($data['zone_ids']);
        }

        $this->specialtyService->sync(
            $provider,
            $data['service_specialty_ids'] ?? null,
            $data['custom_specialties'] ?? null,
        );
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