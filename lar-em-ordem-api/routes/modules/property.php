<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Property\PropertyController;


Route::get('properties/forms-options', [PropertyController::class, 'formOptions']);
Route::apiResource('properties', PropertyController::class)
    ->whereUlid('property');
