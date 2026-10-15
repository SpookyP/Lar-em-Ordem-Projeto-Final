<?php

use App\Http\Controllers\ServiceProvider\AssistanceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('assistance-requests/{assistance_request}/cancel', [AssistanceRequestController::class, 'cancel'])
        ->whereUlid('assistance_request');
        Route::get('assistance-requests/available', [AssistanceRequestController::class, 'available']);
    Route::apiResource('assistance-requests', AssistanceRequestController::class)
        ->except(['destroy'])
        ->whereUlid('assistance_request');
});
