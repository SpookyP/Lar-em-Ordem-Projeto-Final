<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    require __DIR__ . '/modules/vault.php';
    require __DIR__ . '/modules/property.php';
    require __DIR__ . '/modules/resident.php';
    require __DIR__ . '/modules/invoice.php';
    require __DIR__ . '/modules/partner.php';
    require __DIR__ . '/modules/offer.php';
    require __DIR__ . '/modules/service_provider.php';
});


