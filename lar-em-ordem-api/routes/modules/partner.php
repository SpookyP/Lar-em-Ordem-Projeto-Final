<?php

use App\Http\Controllers\PartnerOffer\PartnerController;
use Illuminate\Support\Facades\Route;


Route::get('partners/me', [PartnerController::class, 'showMyProfile']);
Route::apiResource('partners', PartnerController::class)
    ->whereUlid('partner');
