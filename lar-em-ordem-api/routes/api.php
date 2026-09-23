<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    require __DIR__ . '/modules/vault.php';

});

Route::apiResource('properties', 'PropertyController')->middleware('auth:sanctum');
Route::apiResource('residents', 'ResidentController')->middleware('auth:sanctum');
Route::apiResource('addresses', 'AddressController')->middleware('auth:sanctum');
Route::apiResource('partners', PartnerController::class);