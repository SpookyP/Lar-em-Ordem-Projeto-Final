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

    private function authorizeResident(int $userId)
    {
        $resident = $this->service->getResidentByUserId(
            userId: $userId
        );

        if (!$resident) {
            abort(404, 'Resident profile not found.');
        }

        $this->authorize('view', $resident);

        return $resident;
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request): JsonResponse
    {
        $resident = $this->authorizeResident(userId: $request->user()->id);

        return ResidentResource::make($resident)
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResidentRequest $request)
    {
        $this->authorizeResident(userId: $request->user()->id);

        $property = $this->service->updateResident(
            userId: $request->user()->id,
            data: $request->validated()
        );
        return ResidentResource::make($property)
            ->additional(['message' => 'Resident updated successfully.'])
            ->response();
    }
}
