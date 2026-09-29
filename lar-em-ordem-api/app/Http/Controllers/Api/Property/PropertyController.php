<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Services\Property\PropertyService;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;
use App\Http\Resources\Property\PropertyResource;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate(['per_page' => ['sometimes', 'integer','between:1,15'],]);

        $properties = $this->service->getResidentProperties(
            userId: $request->user()->id,
            perPage: $request->integer('per_page', 5)
        );

        return PropertyResource::collection($properties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->service->createResidentProperty(
            userId: $request->user()->id,
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
    public function show(Request $request, int $propertyId): JsonResponse
    {
        $property = $this->service->getResidentPropertyById(
            propertyId: $propertyId,
            userId: $request->user()->id
        );
        return PropertyResource::make($property)
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, int $propertyId)
    {
        $property = $this->service->updateResidentProperty(
            propertyId: $propertyId,
            userId: $request->user()->id,
            data: $request->validated()
        );
        return PropertyResource::make($property)
            ->additional(['message' => 'Property updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, int $propertyId)
    {
        $response = $this->service->deleteResidentProperty(
            propertyId: $propertyId,
            userId: $request->user()->id
        );
        return response()->json(['message' => 'Property was successfully deleted']);
    }
}
