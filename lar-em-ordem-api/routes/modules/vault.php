<?php

use App\Http\Controllers\Api\Vault\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Vault\DocumentController;

Route::middleware('auth:sanctum')->group(function () {
    // Documentos
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

    // Notificações
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});
