<?php

use App\Http\Controllers\PartnerOffer\OfferCategoryController;
use App\Http\Controllers\PartnerOffer\OfferController;
use Illuminate\Support\Facades\Route;

Route::get('offer-categories', [OfferCategoryController::class, 'index']);
Route::get('offers/recommendations', [OfferController::class, 'listActiveOffers']);
Route::apiResource('offers', OfferController::class)
    ->whereUlid('offer');

/*
 * offer-categories          → offer type catalog (partner form).
 * offers                    → the authenticated partner's offers, paginated (?status= filter).
 * offers/recommendations    → every partner's current offers, paginated (?search=, ?category_id=), with the types.
 */