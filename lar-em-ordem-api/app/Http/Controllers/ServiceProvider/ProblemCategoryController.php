<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ProblemCategoryResource;
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
}