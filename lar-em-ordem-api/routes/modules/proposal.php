<?php

use App\Http\Controllers\ServiceProvider\ProposalController;
use Illuminate\Support\Facades\Route;

Route::get('proposals/mine', [ProposalController::class, 'listProviderProposals']);
Route::post('assistance-requests/{assistance_request}/proposals', [ProposalController::class, 'store'])
    ->whereUlid('assistance_request');

Route::patch('proposals/{proposal}', [ProposalController::class, 'update'])
    ->whereUlid('proposal');
Route::patch('proposals/{proposal}/withdraw', [ProposalController::class, 'withdrawByProvider'])
    ->whereUlid('proposal');
Route::patch('proposals/{proposal}/accept', [ProposalController::class, 'acceptByResident'])
    ->whereUlid('proposal');
Route::patch('proposals/{proposal}/reject', [ProposalController::class, 'rejectByResident'])
    ->whereUlid('proposal');
