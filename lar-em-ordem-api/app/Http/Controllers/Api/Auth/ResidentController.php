<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\Resident\ResidentService;
use App\Http\Requests\Resident\UpdateResidentRequest;
use Illuminate\Http\Request;
use App\Http\Resources\Resident\ResidentResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Uid\Ulid;
use App\Services\Auth\ProfileService;

class ResidentController extends Controller
{
    public function __construct(
        protected ResidentService $service,
        protected ProfileService $profileService,
    ) {}

    private function authorizeResident(string|Ulid $userId)
    {
        $ulid = is_string($userId) ? new Ulid($userId) : $userId;

        $resident = $this->service->getResidentByUserId(
            userId: $ulid
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
        $resident = $this->authorizeResident(userId: $request->user()->id);

        $property = $this->service->updateResident(
            userId: $resident->user_id,
            data: $request->validated()
        );
        return ResidentResource::make($property)
            ->additional(['message' => 'Resident updated successfully.'])
            ->response();
    }

    /**
     * Remove the authenticated user's resident profile (soft delete) and the 'resident' role.
     * Same rules as DELETE /api/user/profile: it cannot be the account's only profile.
     */
    public function delete(Request $request): JsonResponse
    {
        $resident = $this->authorizeResident(userId: $request->user()->id);

        $this->authorize('delete', $resident);

        $this->profileService->delete($request->user(), 'resident');

        return response()->json(['message' => 'Resident profile removed successfully.']);
    }
}
