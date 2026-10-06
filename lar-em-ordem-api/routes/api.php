<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/user/profile', [AuthController::class, 'store'])->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    require __DIR__ . '/modules/vault.php';
    require __DIR__ . '/modules/property.php';
    require __DIR__ . '/modules/resident.php';
    require __DIR__ . '/modules/invoice.php';
    require __DIR__ . '/modules/partner.php';
    require __DIR__ . '/modules/offer.php';
    require __DIR__ . '/modules/service_provider.php';
});