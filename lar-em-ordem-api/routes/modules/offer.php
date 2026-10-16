<?php

use App\Http\Controllers\PartnerOffer\OfferController;
use Illuminate\Support\Facades\Route;


Route::get('offers/partner/{partner}', [OfferController::class, 'listByPartner'])
    ->whereUlid('partner');
Route::get('offers/recommendations', [OfferController::class, 'listActiveOffers']);
Route::apiResource('offers', OfferController::class)
    ->whereUlid('offer');

/*
offers                    → as ofertas do parceiro autenticado (todas; filtro ?active=).
offers/partner/{partner}  → as ofertas em vigor de um parceiro (vista pública).
offers/recommendations    → as ofertas em vigor de todos os parceiros.
 */