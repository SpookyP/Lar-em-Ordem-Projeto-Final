<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProviderSpecialty;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProviderSpecialtyService
{
    /**
     * Devolve as especialidades de um prestador, com a categoria e a especialidade do catálogo.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @return Collection
     */
    public function list(ServiceProviderModel $provider): Collection
    {
        return $provider->specialties()->with(['category', 'specialty'])->get();
    }

     /**
     * Acrescenta especialidades ao prestador.
     * Modo fixo se vier service_specialty_ids; modo custom se vier custom_name.
     * Transaction.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param array $data Dados validados (problem_category_id + service_specialty_ids ou custom_name).
     * @return Collection As especialidades criadas, com categoria e especialidade carregadas.
     */
     public function add(ServiceProviderModel $provider, array $data): Collection
    {
        $categoryId = (int) $data['problem_category_id'];

        if (! empty($data['service_specialty_ids'])) {
            $rows = $this->fixedRows($provider, $categoryId, $data['service_specialty_ids']);
        } else {
            $rows = [$this->customRow($provider, $categoryId, $data['custom_name'])];
        }

        $created = DB::transaction(fn () => $provider->specialties()->createMany($rows));

        return $created->load(['category', 'specialty']);
    }

    /**
     * Prepara as linhas das especialidades da lista.
     * Falha (422) se alguma não pertencer à categoria ou se o prestador já a tiver.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param int $categoryId ID da categoria escolhida.
     * @param array<int> $specialtyIds IDs das especialidades do catálogo.
     * @return array Linhas prontas a gravar.
     */
    private function fixedRows(ServiceProviderModel $provider, int $categoryId, array $specialtyIds): array
    {
        $specialties = ServiceSpecialty::whereIn('id', $specialtyIds)->get();

        $wrongCategory = $specialties->filter(fn ($s) => (int) $s->problem_category_id !== $categoryId);
        if ($wrongCategory->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_specialty_ids' => 'Estas especialidades não pertencem à categoria escolhida: '
                    . $wrongCategory->pluck('name')->join(', ') . '.',
            ]);
        }

        $alreadyHas = $provider->specialties()
            ->whereIn('service_specialty_id', $specialtyIds)
            ->with('specialty')
            ->get();
        if ($alreadyHas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_specialty_ids' => 'Já tem estas especialidades: '
                    . $alreadyHas->pluck('specialty.name')->join(', ') . '.',
            ]);
        }

        return $specialties->map(fn ($s) => [
            'problem_category_id'  => $categoryId,
            'service_specialty_id' => $s->id,
            'custom_name'          => null,
        ])->all();
    }

    /**
     * Prepara a linha de uma especialidade própria (custom).
     * Falha (422) se o prestador já tiver uma com o mesmo nome nesta categoria.
     *
     * @param ServiceProviderModel $provider Instância do prestador.
     * @param int $categoryId ID da categoria escolhida.
     * @param string $customName Nome da especialidade própria.
     * @return array Linha pronta a gravar.
     */
    private function customRow(ServiceProviderModel $provider, int $categoryId, string $customName): array
    {
        $exists = $provider->specialties()
            ->where('problem_category_id', $categoryId)
            ->where('custom_name', $customName)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'custom_name' => 'Já tem esta especialidade nesta categoria.',
            ]);
        }

        return [
            'problem_category_id'  => $categoryId,
            'service_specialty_id' => null,
            'custom_name'          => $customName,
        ];
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