<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Property\PropertyController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('properties', PropertyController::class);
});
