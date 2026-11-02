<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SocialAuthController;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|microsoft')
    ->middleware('throttle:100,1');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|microsoft')
    ->middleware('throttle:200,1');