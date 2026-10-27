<?php

use App\Http\Controllers\ServiceProvider\ServiceProviderController;
use Illuminate\Support\Facades\Route;


Route::get('service-providers/me', [ServiceProviderController::class, 'showMyProfile']);
Route::apiResource('service-providers', ServiceProviderController::class)
    ->only(['index', 'show'])
    ->whereUlid('service_provider');