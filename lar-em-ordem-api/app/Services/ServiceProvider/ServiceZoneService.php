<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ServiceZone;
use Illuminate\Database\Eloquent\Collection;

class ServiceZoneService
{
    /**
     * Lists the zones, with optional district and county filters.
     *
     * @param array $filters ['district' => ?string, 'county' => ?string]
     * @return Collection Zones ordered by district, county and locality.
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