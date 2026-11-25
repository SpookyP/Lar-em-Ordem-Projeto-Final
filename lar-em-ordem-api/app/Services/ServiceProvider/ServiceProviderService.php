<?php

namespace App\Services\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ServiceProviderService {

    /** Request fields that aren't provider columns (they are relations). */
    private const RELATION_KEYS = ['zone_ids', 'service_specialty_ids', 'custom_specialties'];

    public function __construct(private ProviderSpecialtyService $specialtyService,  private PersonalDataEraser $eraser) {}

    /**
     * Creates a user's service provider profile and assigns the 'service_provider' role.
     * It can take the zones and specialties (catalog and custom) straight away.
     * Runs in a transaction.
     *
     * @param array $data Validated data (optionally zone_ids, service_specialty_ids, custom_specialties).
     * @param User $owner User who owns the profile.
     * @return ServiceProviderModel The provider with zones and specialties loaded.
     */
    public function create(array $data, User $owner): ServiceProviderModel
    {
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
     * Updates a provider and, when sent, their zones and specialties.
     * Each list sent replaces the previous one; lists not sent stay as they are. Runs in a transaction.
     *
     * @param ServiceProviderModel $serviceProvider Provider to update.
     * @param array $data Validated data for the update.
     * @return ServiceProviderModel The provider with zones and specialties loaded.
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
     * Removes a provider, withdraws their pending proposals (they become 'withdrawn')
     * and takes the 'service_provider' role from the user. All in one transaction.
     *
     * @param ServiceProviderModel $serviceProvider Provider to remove.
     * @return void
     */
     public function delete(ServiceProviderModel $serviceProvider): void
    {
        DB::transaction(function () use ($serviceProvider) {
            $serviceProvider->proposals()
                ->where('status', 'pending')
                ->update(['status' => 'withdrawn']);

            $this->eraser->eraseServiceProvider($serviceProvider);
            $serviceProvider->delete();
            $serviceProvider->user->removeRole('service_provider');
        });
    }

    /**
     * Loads a provider's zones and specialties (with the category and the catalog specialty).
     *
     * @param ServiceProviderModel $provider The provider.
     * @return ServiceProviderModel The same instance, with the relations loaded.
     */
   public function loadFull(ServiceProviderModel $provider): ServiceProviderModel
    {
        return $provider->load(['zones', 'specialties.category', 'specialties.specialty']);
    }

    /**
     * Replaces the provider's zones and/or specialties, only the lists present in the data.
     *
     * @param ServiceProviderModel $provider The provider.
     * @param array $data Validated data.
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
}