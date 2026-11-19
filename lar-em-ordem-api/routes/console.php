<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckDocumentExpiry;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new CheckDocumentExpiry)->dailyAt('08:00');

// Criar Benchmarks do mes ou ano aumaticamente
Schedule::command('benchmarks:monthly')
    ->monthlyOn(15, '03:00') // Meio do mês
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/benchmarks.log'))
    ->onFailure(fn () => logger()->error('O processamento mensal de benchmarks falhou.'));

Schedule::command('benchmarks:annual')
    ->yearlyOn(2, 1, '03:00') // 1 de fevereiro
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/benchmarks.log'))
    ->onFailure(fn () => logger()->error('O processamento anual de benchmarks falhou.'));

// Limpar diariamente PDFs de faturas sem associação e com mais de 24 horas.
Schedule::command('invoices:clean-orphaned-files')
    ->dailyAt('08:15')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/invoices-cleanup.log'))
    ->onFailure(fn () => logger()->error('A limpeza diária dos ficheiros órfãos de faturas falhou.'));