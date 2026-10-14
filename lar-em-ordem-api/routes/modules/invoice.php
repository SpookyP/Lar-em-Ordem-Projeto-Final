<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Invoice\ConsumptionController;
use App\Http\Controllers\Api\Invoice\ConsumptionTypeController;
use App\Http\Controllers\Api\Invoice\MonthlyBenchmarkController;
use App\Http\Controllers\Api\Invoice\AnnualBenchmarkController;


Route::apiResource('invoices', InvoiceController::class)
    ->only(['index', 'store', 'show', 'destroy']);

Route::apiResource('consumptions', ConsumptionController::class)
    ->only(['index', 'show']);

Route::apiResource('consumption-types', ConsumptionTypeController::class)
    ->only(['index']);

    Route::get('benchmarks/monthly', MonthlyBenchmarkController::class);
    Route::get('benchmarks/annual', AnnualBenchmarkController::class);

    // Upload das Faturas
    Route::post('invoices/upload', [InvoiceController::class, 'upload'])->middleware('throttle:10,1');
    Route::get('invoices/{invoiceId}/file', [InvoiceController::class, 'file']);
