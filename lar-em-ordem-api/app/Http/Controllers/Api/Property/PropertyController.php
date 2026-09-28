<?php

namespace App\Http\Controllers\Api\Property;

use Exception;
use App\Models\Property\Property;
use App\Http\Controllers\Controller;
use App\Services\PropertyService;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;
use App\Http\Resources\PropertyResource;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $properties = $this->service->getResidentProperties(
            residentId: $request->user()->id
            );
        return PropertyResource::collection($properties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->service->createResidentProperty(
            residentId: $request->user()->id,
            propertyData: $request->propertyData(),
            contractData: $request->contractData()
        );
        return PropertyResource::make($property)
            ->additional(['message' => 'Property created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $property_id): JsonResponse
    {
        $property = $this->service->getResidentPropertyById(
            propertyId: $property_id,
            residentId: $request->user()->id
        );
        return PropertyResource::make($property)
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, int $property_id)
    {
        $property = $this->service->updateResidentProperty(
            propertyId: $property_id,
            residentId: $request->user()->id,
            data: $request->validated()
        );
        return PropertyResource::make($property)
            ->additional(['message' => 'Property updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $property_id)
    {
        $response = $this->service->deleteResidentProperty(
            propertyId: $property_id,
            residentId: $request->user()->id
        );
        return response()->json(['message'=>'Property was successfully deleted'],204);
    }
}
