<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyService;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;

class PropertyController extends Controller
{
    public function __construct(protected PropertyService $service)
    {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $properties = $this->service->getResidentProperties($request->user()->id);
            return response->json($properties,200);
        } catch(Exception $error) {
            return response->json($error,500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        //
    }
}
