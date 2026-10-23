<?php

use App\Http\Controllers\ServiceProvider\ServiceZoneController;
use Illuminate\Support\Facades\Route;


Route::get('service-zones', [ServiceZoneController::class, 'index']);