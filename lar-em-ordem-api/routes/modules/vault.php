<?php

use App\Http\Controllers\Api\Vault\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Vault\DocumentController;


// Documentos
Route::get('/properties/{property}/documents', [DocumentController::class, 'index']);
Route::post('/properties/{property}/documents', [DocumentController::class, 'store']);

Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

// Notificações
Route::get('/properties/{property}/notifications', [NotificationController::class, 'index']);
Route::get('/properties/{property}/notifications/unread-count', [NotificationController::class, 'unreadCount']);
Route::patch('/properties/{property}/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
