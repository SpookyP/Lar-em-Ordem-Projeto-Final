<?php

use App\Http\Controllers\Api\Vault\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Vault\DocumentController;

Route::middleware('auth:sanctum')->group(function () {
    // Documentos
    Route::post('/documents', [DocumentController::class, 'store']);

    // Notificações
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});
