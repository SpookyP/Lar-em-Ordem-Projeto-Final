<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Collection;

class ProblemCategoryService
{
    /**
     * Devolve todas as categorias de problemas, por ordem alfabética.
     *
     * @return Collection
     */
    public function list(): Collection
    {
        return ProblemCategory::orderBy('name')->get();
    }

    /**
     * Devolve as especialidades de uma categoria, por ordem alfabética.
     *
     * @param ProblemCategory $category Instância da categoria.
     * @return Collection
     */
    public function listSpecialties(ProblemCategory $category): Collection
    {
        return $category->specialties()->orderBy('name')->get();
    }
}