<?php

use App\Http\Controllers\ServiceProvider\AssistanceRequestController;
use App\Http\Controllers\ServiceProvider\RequestAttachmentController;
use Illuminate\Support\Facades\Route;

Route::get('assistance-requests/available', [AssistanceRequestController::class, 'available']);
Route::patch('assistance-requests/{assistance_request}/cancel', [AssistanceRequestController::class, 'cancel'])
    ->whereUlid('assistance_request');
Route::post('assistance-requests/{assistance_request}/attachments', [RequestAttachmentController::class, 'store'])
    ->whereUlid('assistance_request');
Route::delete('assistance-requests/{assistance_request}/attachments/{attachment}', [RequestAttachmentController::class, 'destroy'])
    ->whereUlid(['assistance_request', 'attachment'])
    ->scopeBindings();
Route::apiResource('assistance-requests', AssistanceRequestController::class)
    ->except(['destroy'])
    ->whereUlid('assistance_request');