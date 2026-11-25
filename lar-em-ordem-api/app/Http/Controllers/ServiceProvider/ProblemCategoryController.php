<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceProvider\ProblemCategoryResource;
use App\Services\ServiceProvider\ProblemCategoryService;

class ProblemCategoryController extends Controller
{
    public function __construct(private ProblemCategoryService $service) {}

   /**
    * Lists every problem category with its specialties, in alphabetical order.
    *
    * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Category collection.
    */
    public function index()
    {
        return ProblemCategoryResource::collection($this->service->list());
    }
}