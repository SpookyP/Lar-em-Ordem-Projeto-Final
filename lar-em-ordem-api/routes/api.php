<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartnerOffer\OfferController;
use App\Http\Controllers\PartnerOffer\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::middleware('auth:sanctum')->apiResource('invoices', InvoiceController::class);
Route::get('offers/partner/{partner}', [OfferController::class, 'listByPartner']);
Route::get('offers/recommendations', [OfferController::class, 'listActiveRecommendations']);
Route::apiResource('offers', OfferController::class);