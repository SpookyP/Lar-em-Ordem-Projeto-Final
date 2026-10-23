<?php

use App\Http\Controllers\PartnerOffer\OfferController;
use Illuminate\Support\Facades\Route;


Route::get('offers/recommendations', [OfferController::class, 'listActiveOffers']);
Route::apiResource('offers', OfferController::class)
    ->whereUlid('offer');

/*
offers                    → as ofertas do parceiro autenticado (todas; filtro ?active=).
offers/recommendations    → as ofertas em vigor de todos os parceiros.
 */