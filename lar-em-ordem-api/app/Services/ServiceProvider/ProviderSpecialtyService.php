<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProviderSpecialty;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Validation\ValidationException;


class ProviderSpecialtyService
{
    /**
     * Substitui as especialidades do prestador. Cada lista só é tratada se for enviada (não null):
     * as que já existem mantêm-se, as que saíram da lista são apagadas e as novas são criadas.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array<int>|null $specialtyIds IDs das especialidades da lista (a categoria vem da própria especialidade).
     * @param array|null $customSpecialties Especialidades próprias: [['problem_category_id' => 1, 'name' => '...'], ...].
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
     * Substitui as especialidades da lista do prestador.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array<int> $specialtyIds IDs das especialidades do catálogo.
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
     * Substitui as especialidades próprias (custom) do prestador.
     * Falha (422) se a lista tiver a mesma especialidade repetida na mesma categoria.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array $customSpecialties Lista de ['problem_category_id' => ..., 'name' => ...].
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
    
    /**
     * Remove uma especialidade do prestador.
     *
     * @param ProviderSpecialty $providerSpecialty Especialidade a remover.
     * @return void
     */
     public function remove(ProviderSpecialty $providerSpecialty): void
    {
        $providerSpecialty->delete();
    }
}