<?php

use App\Http\Controllers\Condominium\CondominiumAdminController;
use Illuminate\Support\Facades\Route;

Route::get('condominium-admins/me', [CondominiumAdminController::class, 'showMyProfile']);