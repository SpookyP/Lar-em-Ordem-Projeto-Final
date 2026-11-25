<?php

namespace App\Services\ServiceProvider;

use App\Models\ServiceProvider\ProblemCategory;
use Illuminate\Database\Eloquent\Collection;

class ProblemCategoryService
{
    /**
     * Returns every problem category with its specialties,
     * both in alphabetical order.
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