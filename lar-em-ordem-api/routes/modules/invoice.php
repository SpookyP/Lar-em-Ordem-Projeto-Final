<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Invoice\ConsumptionTypeController;
use App\Http\Controllers\Api\Invoice\MonthlyBenchmarkSeriesController;
use App\Http\Controllers\Api\Invoice\AnnualBenchmarkSeriesController;

Route::apiResource('invoices', InvoiceController::class)
    ->only(['index', 'store', 'show', 'destroy']);

Route::apiResource('consumption-types', ConsumptionTypeController::class)
    ->only(['index']);
    
Route::get('benchmarks/monthly/series', MonthlyBenchmarkSeriesController::class);
Route::get('benchmarks/annual/series', AnnualBenchmarkSeriesController::class);

// Upload das Faturas
Route::post('invoices/upload', [InvoiceController::class, 'upload']);
Route::get('invoices/{invoiceId}/file/content', [InvoiceController::class, 'fileContent'])
    ->whereUlid('invoiceId');
