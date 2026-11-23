<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\Property;
use App\Services\Property\PropertyService;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Http\Requests\PerPageRequest;
use App\Http\Resources\Property\PropertyResource;
use App\Http\Resources\Property\PropertyListResource;
use Illuminate\Http\JsonResponse;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {
        $this->authorizeResource(Property::class, 'property');
    }

    public function index(PerPageRequest $request)
    {
        $properties = $this->service->getResidentProperties(
            user: $request->user(),
            perPage: $request->integer('per_page', 5)
        );

        return PropertyListResource::collection($properties);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->service->createResidentProperty(
            user: $request->user(),
            propertyData: $request->propertyData(),
            contractData: $request->contractData(),
            addressData: $request->addressData(),
        );
        return PropertyResource::make($property)
            ->additional(['message' => 'Casa criada.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Property $property): JsonResponse
    {
        return PropertyResource::make($this->service->withDetails($property))->response();
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $updatedProperty = $this->service->updateResidentProperty(
            property: $property,
            propertyData: $request->propertyData(),
            contractData: $request->contractData(),
            addressData: $request->addressData(),
        );

        return PropertyResource::make($updatedProperty)
            ->additional(['message' => 'Casa atualizada.'])
            ->response();
    }

    public function destroy(Property $property)
    {
        $this->service->deleteResidentProperty($property);
        return response()->json(['message' => 'Casa eliminada.']);
    }

    /**
     * Options for the home form: property types, typologies and resident types.
     */
    public function formOptions(): JsonResponse
    {
        $this->authorize('viewOptions', Property::class);
 
        return response()->json([
            'data' => $this->service->getPropertyForms()
        ]);
    }
}