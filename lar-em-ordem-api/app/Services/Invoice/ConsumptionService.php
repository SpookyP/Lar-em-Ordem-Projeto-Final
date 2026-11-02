<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Consumption;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ConsumptionService
{
    /**
     * Obter a lista paginada de consumos do utilizador.
     */
    public function getConsumptions(string  $residentId, ?string  $propertyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Consumption::query()
            ->whereHas('invoice', function ($q) use ($residentId) {
                $q->where('resident_id', $residentId);
            })
            ->with(['invoice', 'property', 'consumptionType', 'items'])
            ->latest()
            ->latest('id');

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obter um consumo específico pelo ID, validando o acesso do utilizador.
     */
    public function getConsumptionById(string  $consumptionId, string  $residentId): Consumption
    {
        $consumption = Consumption::query()
            ->where('id', $consumptionId)
            ->whereHas('invoice', function ($q) use ($residentId) {
                $q->where('resident_id', $residentId);
            })
            ->with(['invoice', 'property', 'consumptionType', 'items'])
            ->first();

        if (!$consumption) {
            abort(404, 'Consumption record not found.');
        }

        return $consumption;
    }
}