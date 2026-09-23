<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ResidentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('resident', ResidentController::class);
});