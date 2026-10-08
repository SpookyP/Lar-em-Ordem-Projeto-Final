<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Eloquent\Collection;

class ServiceZoneService
{
    /**
     * Lista as zonas, com filtros opcionais por distrito e concelho.
     *
     * @param array $filters ['district' => ?string, 'county' => ?string]
     */
    public function list(array $filters = []): Collection
    {
        return ServiceZone::query()
            ->when($filters['district'] ?? null, fn ($q, $district) => $q->where('district', $district))
            ->when($filters['county'] ?? null, fn ($q, $county) => $q->where('county', $county))
            ->orderBy('district')
            ->orderBy('county')
            ->orderBy('location')
            ->get();
    }
}