<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|microsoft')
    ->middleware('throttle:100,1,oauth-redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|microsoft')
    ->middleware('throttle:200,1,oauth-callback');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest:'.config('fortify.guard'), 'throttle:5,1,forgot-password'])
    ->name('password.email');