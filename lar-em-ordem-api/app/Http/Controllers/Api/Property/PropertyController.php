<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\Property;
use App\Services\Property\PropertyService;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;
use App\Http\Resources\Property\PropertyResource;
use App\Http\Resources\Property\PropertyListResource;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {
        $this->authorizeResource(Property::class, 'property');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate(['per_page' => ['sometimes', 'integer', 'between:1,15'],]);

        $properties = $this->service->getResidentProperties(
            user: $request->user(),
            perPage: $request->integer('per_page', 5)
        );

        return PropertyListResource::collection($properties);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->service->createResidentProperty(
            user: $request->user(),
            propertyData: $request->propertyData(),
            contractData: $request->contractData(),
            addressData: $request->addressData(),
        );
        return PropertyResource::make($property)
            ->additional(['message' => 'Property created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): JsonResponse
    {
        $property->load([
            'address',
            'propertyType',
            'propertyTypology',
            'contracts' => fn($q) => $q->where('is_active', true)->with('residentType'),
        ]);

        return PropertyResource::make($property)->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $updatedProperty = $this->service->updateResidentProperty(
            property: $property,
            propertyData: $request->propertyData(),
            contractData: $request->contractData(),
            addressData: $request->addressData(),
        );

        return PropertyResource::make($updatedProperty)
            ->additional(['message' => 'Property updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        $this->service->deleteResidentProperty($property);
        return response()->json(['message' => 'Property was successfully deleted']);
    }

    /**
     * Displays forms data.
     */
    public function formOptions(): JsonResponse
    {
        $this->authorize('viewOptions', Property::class);
 
        return response()->json([
            'data' => $this->service->getPropertyForms()
        ]);
    }

    public function terminateContract(Request $request, Property $property): JsonResponse
    {
        $this->authorize('terminate', $property);

        $terminated = $this->service->terminateContract(
            property: $property,
            user: $request->user()
        );

        if (!$terminated) {
            return response()->json([
                'message' => 'Contract not found or already inactive.'
            ], 404);
        }

        return response()->json([
            'message' => 'Contract terminated successfully.'
        ]);
    }
}
