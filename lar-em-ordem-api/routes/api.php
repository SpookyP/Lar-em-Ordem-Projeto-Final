<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialRegisterController;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/user/profile', [AuthController::class, 'store'])->middleware('auth:sanctum');

Route::get('/auth/social/pending', [SocialRegisterController::class, 'show'])->middleware('throttle:20,1');
Route::post('/auth/social/register', [SocialRegisterController::class, 'store'])->middleware('throttle:10,1');

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum','throttle:2000,10'])->group(function () {
        require __DIR__ . '/modules/vault.php';
        require __DIR__ . '/modules/property.php';
        require __DIR__ . '/modules/resident.php';
        require __DIR__ . '/modules/invoice.php';
        require __DIR__ . '/modules/partner.php';
        require __DIR__ . '/modules/offer.php';
        require __DIR__ . '/modules/service_provider.php';
        require __DIR__ . '/modules/service_zone.php';
        require __DIR__ . '/modules/problem_category.php';
        require __DIR__ . '/modules/assistance_request.php';
        require __DIR__ . '/modules/proposal.php';
        require __DIR__ . '/modules/condominium.php';
    });
});

Route::delete('/user/profile', [AuthController::class, 'destroyProfile'])->middleware('auth:sanctum');
Route::delete('/user', [AuthController::class, 'destroyAccount'])->middleware('auth:sanctum');