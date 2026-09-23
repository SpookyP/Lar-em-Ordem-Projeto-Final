<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    require __DIR__ . '/modules/vault.php';
    require __DIR__ . '/modules/property.php';
    require __DIR__ . '/modules/resident.php';
    require __DIR__ . '/modules/address.php';

});

Route::apiResource('partners', PartnerController::class);