<?php

namespace App\Console\Commands;

class RunBenchmarkBackfill extends RunBenchmarkScript
{
    protected $signature = 'benchmarks:backfill';

    protected $description = 'Cria os benchmarks históricos';

    public function handle(): int
    {
        $this->info('A criar benchmarks históricos...');

        $result = $this->runScript('backfill_benchmark.py');

        if ($result === self::SUCCESS) {
            $this->info('Benchmarks históricos criados com sucesso.');
        }

        return $result;
    }
}