<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Property\PropertyController;


Route::get('properties/forms-options', [PropertyController::class, 'formOptions']);
Route::delete('properties/{property}/terminate', [PropertyController::class, 'terminateContract'])
    ->whereUlid('property');
Route::apiResource('properties', PropertyController::class)
    ->whereUlid('property');
