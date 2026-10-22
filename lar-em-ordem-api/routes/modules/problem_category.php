<?php

use App\Http\Controllers\ServiceProvider\ProblemCategoryController;
use Illuminate\Support\Facades\Route;


Route::get('problem-categories', [ProblemCategoryController::class, 'index']);
