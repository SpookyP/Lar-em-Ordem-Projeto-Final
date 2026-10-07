<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunMonthlyBenchmark extends Command
{
    protected $signature = 'benchmarks:monthly';

    protected $description = 'Executa o benchmark mensal';

    public function handle(): int
    {
        $process = new Process([
            'python',
            base_path('scripts/Benchmark_creation_tests/monthly_benchmark.py'),
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('O benchmark mensal falhou.');
            return self::FAILURE;
        }

        $this->info('Benchmark mensal executado com sucesso.');

        return self::SUCCESS;
    }
}