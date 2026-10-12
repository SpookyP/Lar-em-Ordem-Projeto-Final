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
    public function list(ServiceProviderModel $provider): Collection
    {
        return $provider->specialties()->with(['category', 'specialty'])->get();
    }

    /**
     * Acrescenta uma especialidade ao prestador.
     * Modo fixo se vier service_specialty_id; modo custom se vier custom_name.
     */
     public function add(ServiceProviderModel $provider, array $data): Collection
    {
        $categoryId = (int) $data['problem_category_id'];

        if (! empty($data['service_specialty_ids'])) {
            $rows = $this->fixedRows($provider, $categoryId, $data['service_specialty_ids']);
        } else {
            $rows = [$this->customRow($provider, $categoryId, $data['custom_name'])];
        }

        // Tudo ou nada: se uma falhar, nenhuma fica gravada
        $created = DB::transaction(fn () => $provider->specialties()->createMany($rows));

        return $created->load(['category', 'specialty']);
    }

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
    
     public function remove(ProviderSpecialty $providerSpecialty): void
    {
        $providerSpecialty->delete();
    }
}