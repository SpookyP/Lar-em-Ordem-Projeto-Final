<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunBenchmarkBackfill extends Command
{
    protected $signature = 'benchmarks:backfill';

    protected $description = 'Cria os benchmarks históricos';

    public function handle(): int
    {
        $process = new Process([
            'python',
            base_path('scripts/Benchmark_creation_tests/Backfill_benchmark.py'),
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('O backfill dos benchmarks falhou.');
            return self::FAILURE;
        }

        $this->info('Backfill dos benchmarks executado com sucesso.');

        return self::SUCCESS;
    }
}