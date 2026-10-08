<?php

use App\Http\Controllers\PartnerOffer\OfferController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('offers/partner/{partner}', [OfferController::class, 'listByPartner'])
        ->whereUlid('partner');
    Route::get('offers/recommendations', [OfferController::class, 'listActiveOffers']);
    Route::apiResource('offers', OfferController::class)
        ->whereUlid('offer');
});

/*
offers/partner/{partner} devolve { "data": [ {oferta}, {oferta} ] }, uma lista de ofertas de 1 partner.
 */