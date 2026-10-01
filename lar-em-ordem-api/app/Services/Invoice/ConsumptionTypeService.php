<?php

namespace App\Services\Invoice;

use App\Models\Invoice\ConsumptionType;
use Illuminate\Database\Eloquent\Collection;

class ConsumptionTypeService
{
    /**
     * Lista completa (tabela pequena e estática, sem paginação).
     */
    public function getAll(): Collection
    {
        return ConsumptionType::query()->orderBy('name')->get();
    }
}