<?php

use App\Http\Controllers\ServiceProvider\ServiceZoneController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('service-zones', ServiceZoneController::class)->only(['index', 'show']);
});