<?php

use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('service-providers/active', [ServiceProviderController::class, 'listActiveProviders']);
    Route::put('service-providers/{service_provider}/zones', [ServiceProviderController::class, 'syncZones']);
    Route::apiResource('service-providers', ServiceProviderController::class)->except(['index']);
});