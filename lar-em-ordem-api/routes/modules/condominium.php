<?php

use App\Http\Controllers\Condominium\CondominiumAdminController;
use App\Http\Controllers\Condominium\CondominiumController;
use App\Http\Controllers\Condominium\CondominiumNoticeController;
use App\Http\Controllers\Condominium\CondominiumDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('condominium-admins/me', [CondominiumAdminController::class, 'showMyProfile']);

Route::post('condominiums/join', [CondominiumController::class, 'join'])
    ->middleware('throttle:5,1,condominium-join');
Route::post('condominiums/claim', [CondominiumController::class, 'claim'])
    ->middleware('throttle:5,1,condominium-claim');
Route::post('condominiums/{condominium}/transfer', [CondominiumController::class, 'startTransfer'])
    ->whereUlid('condominium');
Route::delete('condominiums/{condominium}/transfer', [CondominiumController::class, 'cancelTransfer'])
    ->whereUlid('condominium');
Route::patch('condominiums/{condominium}/leave', [CondominiumController::class, 'leave'])
    ->whereUlid('condominium');
Route::apiResource('condominiums', CondominiumController::class)
    ->whereUlid('condominium');
    
Route::get('condominiums/{condominium}/notices', [CondominiumNoticeController::class, 'index'])
    ->whereUlid('condominium');
Route::post('condominiums/{condominium}/notices', [CondominiumNoticeController::class, 'store'])
    ->whereUlid('condominium');
Route::patch('condominium-notices/{condominium_notice}/read', [CondominiumNoticeController::class, 'markRead'])
    ->whereUlid('condominium_notice');
Route::patch('condominium-notices/{condominium_notice}', [CondominiumNoticeController::class, 'update'])
    ->whereUlid('condominium_notice');
Route::delete('condominium-notices/{condominium_notice}', [CondominiumNoticeController::class, 'destroy'])
    ->whereUlid('condominium_notice');

Route::get('properties/{property}/condominium', [CondominiumController::class, 'showForProperty'])
    ->whereUlid('property');
Route::get('properties/{property}/condominium-notices', [CondominiumNoticeController::class, 'bell'])
    ->whereUlid('property');
Route::patch('properties/{property}/condominium-notices/read-all', [CondominiumNoticeController::class, 'markAllRead'])
    ->whereUlid('property');
Route::patch('condominium-notices/{condominium_notice}/dismiss', [CondominiumNoticeController::class, 'dismiss'])
    ->whereUlid('condominium_notice');

Route::get('condominiums/{condominium}/documents', [CondominiumDocumentController::class, 'index'])
    ->whereUlid('condominium');
Route::post('condominiums/{condominium}/documents', [CondominiumDocumentController::class, 'store'])
    ->whereUlid('condominium');
Route::get('condominium-documents/{condominium_document}/download', [CondominiumDocumentController::class, 'download'])
    ->whereUlid('condominium_document');
Route::patch('condominium-documents/{condominium_document}', [CondominiumDocumentController::class, 'update'])
    ->whereUlid('condominium_document');
Route::delete('condominium-documents/{condominium_document}', [CondominiumDocumentController::class, 'destroy'])
    ->whereUlid('condominium_document');
Route::get('properties/{property}/condominium-documents', [CondominiumDocumentController::class, 'indexForProperty'])
    ->whereUlid('property');
