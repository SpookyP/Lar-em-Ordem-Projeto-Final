<?php

use App\Http\Controllers\ServiceProvider\AssistanceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('assistance-requests/available', [AssistanceRequestController::class, 'available']);
Route::get('assistance-attachments/{request_attachment}/content', [AssistanceRequestController::class, 'attachmentContent'])
    ->whereUlid('request_attachment');
Route::patch('assistance-requests/{assistance_request}/cancel', [AssistanceRequestController::class, 'cancel'])
    ->whereUlid('assistance_request');
Route::apiResource('assistance-requests', AssistanceRequestController::class)
    ->except(['destroy'])
    ->whereUlid('assistance_request');