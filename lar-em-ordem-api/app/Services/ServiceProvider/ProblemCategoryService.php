<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Collection;

class ProblemCategoryService
{
    /**
     * Devolve todas as categorias de problemas com as respetivas especialidades,
     * ambas por ordem alfabética.
     *
     * @return Collection
     */
    public function list(): Collection
    {
        return ProblemCategory::with(['specialties' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();
    }
}