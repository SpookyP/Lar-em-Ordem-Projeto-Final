<?php

use App\Http\Controllers\Condominium\CondominiumAdminController;
use App\Http\Controllers\Condominium\CondominiumController;
use Illuminate\Support\Facades\Route;

Route::get('condominium-admins/me', [CondominiumAdminController::class, 'showMyProfile']);

Route::post('condominiums/join', [CondominiumController::class, 'join'])
    ->middleware('throttle:5,1');
    Route::post('condominiums/claim', [CondominiumController::class, 'claim'])
    ->middleware('throttle:5,1');
Route::post('condominiums/{condominium}/transfer', [CondominiumController::class, 'startTransfer'])
    ->whereUlid('condominium');
Route::delete('condominiums/{condominium}/transfer', [CondominiumController::class, 'cancelTransfer'])
    ->whereUlid('condominium');
Route::patch('condominiums/{condominium}/leave', [CondominiumController::class, 'leave'])
    ->whereUlid('condominium');
Route::apiResource('condominiums', CondominiumController::class)
    ->whereUlid('condominium');