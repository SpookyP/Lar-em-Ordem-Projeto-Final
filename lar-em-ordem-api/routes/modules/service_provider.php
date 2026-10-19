<?php

use App\Http\Controllers\ServiceProvider\ProviderSpecialtyController;
use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use Illuminate\Support\Facades\Route;


Route::get('service-providers/active', [ServiceProviderController::class, 'listActiveProviders']);
Route::get('service-providers/me', [ServiceProviderController::class, 'showMyProfile']);

Route::put('service-providers/{service_provider}/zones', [ServiceProviderController::class, 'syncZones'])
    ->whereUlid('service_provider');
Route::get('service-providers/{service_provider}/specialties', [ProviderSpecialtyController::class, 'index'])
    ->whereUlid('service_provider');
Route::post('service-providers/{service_provider}/specialties', [ProviderSpecialtyController::class, 'store'])
    ->whereUlid('service_provider');
Route::delete('service-providers/{service_provider}/specialties/{specialty}', [ProviderSpecialtyController::class, 'destroy'])
    ->whereUlid(['service_provider', 'specialty'])
    ->scopeBindings();
Route::apiResource('service-providers', ServiceProviderController::class)
    ->except(['index'])
    ->whereUlid('service_provider');