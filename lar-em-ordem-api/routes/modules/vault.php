<?php

use App\Http\Controllers\Api\Vault\DocumentCategoryController;
use App\Http\Controllers\Api\Vault\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Vault\DocumentController;


// Documentos
Route::get('/properties/{property}/documents', [DocumentController::class, 'index'])
    ->whereUlid('property');
Route::post('/properties/{property}/documents', [DocumentController::class, 'store'])
    ->whereUlid('property');

Route::get('/documents/{document}', [DocumentController::class, 'show'])
    ->whereUlid('document');
Route::put('/documents/{document}', [DocumentController::class, 'update'])
    ->whereUlid('document');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->whereUlid('document');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
    ->whereUlid('document');
Route::get('/document-categories', [DocumentCategoryController::class, 'index']);

// Notificações
Route::get('/properties/{property}/notifications', [NotificationController::class, 'index'])
    ->whereUlid('property');
Route::get('/properties/{property}/notifications/unread-count', [NotificationController::class, 'unreadCount'])
    ->whereUlid('property');
Route::patch('/properties/{property}/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
    ->whereUlid('property');

Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
    ->whereUlid('notification');
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])
    ->whereUlid('notification');
Route::patch('/notifications/{notification}/unread', [NotificationController::class, 'markAsUnread'])
    ->whereUlid('notification');
