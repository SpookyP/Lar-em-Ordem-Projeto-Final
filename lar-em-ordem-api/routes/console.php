<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckDocumentExpiry;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckDocumentExpiry)->dailyAt('00:00');

// Criar Benchmarks do mes ou ano aumaticamente
Schedule::command('benchmarks:monthly')
    ->monthlyOn(15, '03:00');

Schedule::command('benchmarks:annual')
    ->yearlyOn(2, 1, '03:00');
