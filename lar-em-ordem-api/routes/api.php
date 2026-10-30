<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialRegisterController;

require __DIR__ . '/modules/auth.php';

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