<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialRegisterController;
use App\Http\Controllers\Api\Auth\PendingRegistrationController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\PrivacyController;

// every throttle has its own name: unnamed ones share one counter per IP (or per user) across all routes

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/user/profile', [AuthController::class, 'store'])->middleware('auth:sanctum');
Route::get('/user/privacy', [PrivacyController::class, 'show'])->middleware('auth:sanctum');
Route::put('/user/privacy', [PrivacyController::class, 'update'])->middleware('auth:sanctum');

Route::post('/auth/register', [PendingRegistrationController::class, 'register'])->middleware('throttle:10,1,register');
Route::post('/auth/register/confirm', [PendingRegistrationController::class, 'confirm'])->middleware('throttle:20,1,register-confirm');
Route::post('/auth/register/resend', [PendingRegistrationController::class, 'resend'])->middleware('throttle:3,1,register-resend');

Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1,email-verify'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware(['auth:sanctum', 'throttle:3,1,email-resend']);

Route::get('/auth/social/pending', [SocialRegisterController::class, 'show'])->middleware('throttle:20,1,social-pending');
Route::post('/auth/social/register', [SocialRegisterController::class, 'store'])->middleware('throttle:10,1,social-register');

Route::delete('/user/profile', [AuthController::class, 'destroyProfile'])->middleware('auth:sanctum');
Route::delete('/user', [AuthController::class, 'destroyAccount'])->middleware('auth:sanctum');