<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {

    require __DIR__ . '/modules/vault.php';

});

Route::apiResource('properties', 'PropertyController');
Route::apiResource('residents', 'ResidentController');
Route::apiResource('addresses', 'AddressController');