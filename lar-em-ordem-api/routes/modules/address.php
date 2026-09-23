<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Property\AddressController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('addresses', AddressController::class);
});