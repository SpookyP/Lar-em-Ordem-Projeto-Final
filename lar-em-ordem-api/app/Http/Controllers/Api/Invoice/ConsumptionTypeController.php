<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Services\Invoice\ConsumptionTypeService;
use App\Http\Resources\Invoice\ConsumptionTypeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsumptionTypeController extends Controller
{
    public function __construct(private ConsumptionTypeService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return ConsumptionTypeResource::collection(
            $this->service->getAll()
        );
    }
}