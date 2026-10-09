<?php

use App\Http\Controllers\PartnerOffer\PartnerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('partners/offers/{partner}', [PartnerController::class, 'showWithOffers'])
        ->whereUlid('partner');
    Route::apiResource('partners', PartnerController::class)
        ->whereUlid('partner');
});

/*
partners/offers/{partner} devolve { "data": { parceiro, "offers": [...] } }, um objeto do parceiro com todas as suas ofertas.
 */