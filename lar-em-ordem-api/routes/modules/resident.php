<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\ResidentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/resident/profile',[ResidentController::class,'show']);
    Route::put('/resident/profile',[ResidentController::class,'update']);
    Route::delete('/resident/profile',[ResidentController::class,'delete']);
});