<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Vault\DocumentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/documents', [DocumentController::class, 'store']);
});
