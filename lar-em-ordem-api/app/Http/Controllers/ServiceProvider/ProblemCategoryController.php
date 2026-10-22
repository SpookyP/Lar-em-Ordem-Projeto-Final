<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ProblemCategoryResource;
use App\Http\Resources\ServiceProvider\ServiceSpecialtyResource;
use App\Models\ServiceProvider\ProblemCategory;
use App\Services\ServiceProvider\ProblemCategoryService;

class ProblemCategoryController extends Controller
{
    public function __construct(private ProblemCategoryService $service) {}

   /**
     * Lista todas as categorias de problemas com as respetivas especialidades, por ordem alfabética.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de categorias.
     */
    public function index()
    {
        return ProblemCategoryResource::collection($this->service->list());
    }

     /**
     * Lista as especialidades de uma categoria, por ordem alfabética.
     *
     * @param ProblemCategory $problemCategory Instância da categoria.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Coleção de especialidades.
     */
    public function specialties(ProblemCategory $problemCategory)
    {
        return ServiceSpecialtyResource::collection($this->service->listSpecialties($problemCategory));
    }
}