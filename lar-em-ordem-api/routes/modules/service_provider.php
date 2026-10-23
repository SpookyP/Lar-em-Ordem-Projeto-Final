<?php

use App\Http\Controllers\ServiceProvider\ProviderSpecialtyController;
use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use Illuminate\Support\Facades\Route;


Route::get('service-providers/me', [ServiceProviderController::class, 'showMyProfile']);
Route::delete('service-providers/{service_provider}/specialties/{specialty}', [ProviderSpecialtyController::class, 'destroy'])
    ->whereUlid(['service_provider', 'specialty'])
    ->scopeBindings();
Route::apiResource('service-providers', ServiceProviderController::class)
    ->whereUlid('service_provider');