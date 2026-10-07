<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunAnnualBenchmark extends Command
{
    protected $signature = 'benchmarks:annual';

    protected $description = 'Executa o benchmark anual';

    public function handle(): int
    {
        $process = new Process([
            'python',
            base_path('scripts/Benchmark_creation_tests/annual_benchmark.py'),
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('O benchmark anual falhou.');
            return self::FAILURE;
        }

        $this->info('Benchmark anual executado com sucesso.');

        return self::SUCCESS;
    }
}