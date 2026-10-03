<?php

use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('service-providers/active', [ServiceProviderController::class, 'listActiveProviders']);
    Route::apiResource('service-providers', ServiceProviderController::class)->except(['index']);
});