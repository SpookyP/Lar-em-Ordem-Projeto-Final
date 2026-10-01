<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Invoice\ConsumptionController;
use App\Http\Controllers\Api\Invoice\ConsumptionTypeController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('invoices', InvoiceController::class)
        ->only(['index', 'store', 'show', 'destroy']);

    Route::apiResource('consumptions', ConsumptionController::class)
        ->only(['index', 'show']);

    Route::apiResource('consumption-types', ConsumptionTypeController::class)
        ->only(['index']);
});