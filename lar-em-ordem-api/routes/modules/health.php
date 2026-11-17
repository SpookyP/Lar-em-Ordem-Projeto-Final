<?php

use App\Http\Controllers\Api\HealthController;

Route::get('health', HealthController::class)->middleware('throttle:30,1,health');