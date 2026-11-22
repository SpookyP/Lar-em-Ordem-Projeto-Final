<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckDocumentExpiry;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckDocumentExpiry)->dailyAt('08:00');

// benchmarks are created automatically: monthly (the last 3 complete months) and yearly (the previous year)
Schedule::command('benchmarks:monthly')
    ->monthlyOn(15, '03:00') // mid-month
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/benchmarks.log'))
    ->onFailure(fn () => logger()->error('O processamento mensal de benchmarks falhou.'));

Schedule::command('benchmarks:annual')
    ->yearlyOn(2, 1, '03:00') // 1 February
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/benchmarks.log'))
    ->onFailure(fn () => logger()->error('O processamento anual de benchmarks falhou.'));

// daily cleanup of invoice PDFs with no invoice that are older than 24 hours
Schedule::command('invoices:clean-orphaned-files')
    ->dailyAt('08:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/invoices-cleanup.log'))
    ->onFailure(fn () => logger()->error('A limpeza diária dos ficheiros órfãos de faturas falhou.'));