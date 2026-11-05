<?php

use App\Http\Controllers\PartnerOffer\OfferCategoryController;
use App\Http\Controllers\PartnerOffer\OfferController;
use Illuminate\Support\Facades\Route;

Route::get('offer-categories', [OfferCategoryController::class, 'index']);
Route::get('offers/recommendations', [OfferController::class, 'listActiveOffers']);
Route::apiResource('offers', OfferController::class)
    ->whereUlid('offer');

/*
* offer-categories          → catálogo de tipos de oferta (formulário do parceiro).
* offers                    → as ofertas do parceiro autenticado, paginadas (filtro ?status=).
* offers/recommendations    → as ofertas em vigor de todos os parceiros, paginadas (?search=, ?category_id=), com os tipos.
*/