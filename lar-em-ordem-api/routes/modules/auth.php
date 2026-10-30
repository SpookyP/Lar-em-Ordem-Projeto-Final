<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialRegisterController;
use App\Http\Controllers\Api\Auth\PendingRegistrationController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/user/profile', [AuthController::class, 'store'])->middleware('auth:sanctum');

Route::post('/auth/register', [PendingRegistrationController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/register/confirm', [PendingRegistrationController::class, 'confirm'])->middleware('throttle:20,1');
Route::post('/auth/register/resend', [PendingRegistrationController::class, 'resend'])->middleware('throttle:3,1');

Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:3,1']);

Route::get('/auth/social/pending', [SocialRegisterController::class, 'show'])->middleware('throttle:20,1');
Route::post('/auth/social/register', [SocialRegisterController::class, 'store'])->middleware('throttle:10,1');

Route::delete('/user/profile', [AuthController::class, 'destroyProfile'])->middleware('auth:sanctum');
Route::delete('/user', [AuthController::class, 'destroyAccount'])->middleware('auth:sanctum');