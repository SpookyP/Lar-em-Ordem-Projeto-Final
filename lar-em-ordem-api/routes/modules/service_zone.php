<?php

use App\Http\Controllers\ServiceProvider\ServiceZoneController;
use Illuminate\Support\Facades\Route;

Route::apiResource('service-zones', ServiceZoneController::class)
        ->only(['index', 'show'])
        ->whereNumber('service_zone');