<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Resident\ResidentService;
use App\Http\Requests\Resident\UpdateResidentRequest;
use Illuminate\Http\Request;
use App\Http\Resources\Resident\ResidentResource;
use Illuminate\Http\JsonResponse;

class ResidentController extends Controller
{
    public function __construct(protected ResidentService $service)
    {}

    /**
     * Display the specified resource.
     */
    public function show(Request $request): JsonResponse
    {
        $resident = $this->service->getResidentByUserId(
            userId: $request->user()->id
        );
        return ResidentResource::make($resident)
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResidentRequest $request)
    {
        $property = $this->service->updateResident(
            userId: $request->user()->id,
            data: $request->validated()
        );
        return ResidentResource::make($property)
            ->additional(['message' => 'Resident updated successfully.'])
            ->response();
    }
}
