<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Property\PropertyContractController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('properties/{property}/contracts', [PropertyContractController::class, 'store']);
    Route::post('properties/{property}/residents/{resident}', [PropertyContractController::class, 'addResident']);
    Route::delete('properties/{property}/residents/{resident}', [PropertyContractController::class, 'removeResident']);
    Route::patch('contracts/{contract}/terminate', [PropertyContractController::class, 'terminate']);
});