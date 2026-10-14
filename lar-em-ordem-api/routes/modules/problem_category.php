<?php

use App\Http\Controllers\ServiceProvider\ProblemCategoryController;
use Illuminate\Support\Facades\Route;


Route::get('problem-categories', [ProblemCategoryController::class, 'index']);
Route::get('problem-categories/{problem_category}/specialties', [ProblemCategoryController::class, 'specialties'])
    ->whereNumber('problem_category');
