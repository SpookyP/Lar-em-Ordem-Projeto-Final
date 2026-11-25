<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Validation\ValidationException;


class ProviderSpecialtyService
{
    /**
     * Replaces the provider's specialties. Each list is only handled when sent (not null):
     * the ones that already exist stay, the ones taken off the list are deleted and the new ones are created.
     *
     * @param ServiceProviderModel $provider The provider.
     * @param array<int>|null $specialtyIds Catalog specialty ids (the category comes from the specialty).
     * @param array|null $customSpecialties Custom specialties: [['problem_category_id' => 1, 'name' => '...'], ...].
     * @return void
     */
    public function sync(ServiceProviderModel $provider, ?array $specialtyIds, ?array $customSpecialties): void
    {
        if ($specialtyIds !== null) {
            $this->syncFixed($provider, $specialtyIds);
        }

        if ($customSpecialties !== null) {
            $this->syncCustom($provider, $customSpecialties);
        }
    }

    /**
     * Replaces the provider's catalog specialties.
     *
     * @param ServiceProviderModel $provider The provider.
     * @param array<int> $specialtyIds Catalog specialty ids.
     * @return void
     */
    private function syncFixed(ServiceProviderModel $provider, array $specialtyIds): void
    {
        $fixed = $provider->specialties()->whereNotNull('service_specialty_id');

        (clone $fixed)->whereNotIn('service_specialty_id', $specialtyIds)->delete();

        $current = (clone $fixed)->pluck('service_specialty_id')->all();

        $rows = ServiceSpecialty::whereIn('id', array_diff($specialtyIds, $current))->get()
            ->map(fn ($s) => [
                'problem_category_id'  => $s->problem_category_id,
                'service_specialty_id' => $s->id,
                'custom_name'          => null,
            ])->all();

        $provider->specialties()->createMany($rows);
    }

    /**
     * Replaces the provider's custom specialties.
     * Fails (422) if the list repeats the same specialty in the same category.
     *
     * @param ServiceProviderModel $provider The provider.
     * @param array $customSpecialties List of ['problem_category_id' => ..., 'name' => ...].
     * @return void
     */
    private function syncCustom(ServiceProviderModel $provider, array $customSpecialties): void
    {
        $key = fn ($row) => $row['problem_category_id'] . '|' . mb_strtolower($row['custom_name']);

        $wanted = collect($customSpecialties)->map(fn ($c) => [
            'problem_category_id'  => (int) $c['problem_category_id'],
            'service_specialty_id' => null,
            'custom_name'          => $c['name'],
        ]);

        if ($wanted->unique($key)->count() !== $wanted->count()) {
            throw ValidationException::withMessages([
                'custom_specialties' => 'A lista de especialidades próprias tem valores repetidos.',
            ]);
        }

        $current = $provider->specialties()->whereNull('service_specialty_id')->get();
        $wantedKeys = $wanted->map($key);
        $currentKeys = $current->map($key);

        $current->reject(fn ($s) => $wantedKeys->contains($key($s)))->each->delete();

        $provider->specialties()->createMany(
            $wanted->reject(fn ($c) => $currentKeys->contains($key($c)))->values()->all()
        );
    } 
}