<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Collection;

class ProblemCategoryService
{
    public function list(): Collection
    {
        return ProblemCategory::orderBy('name')->get();
    }

    public function listSpecialties(ProblemCategory $category): Collection
    {
        return $category->specialties()->orderBy('name')->get();
    }
}