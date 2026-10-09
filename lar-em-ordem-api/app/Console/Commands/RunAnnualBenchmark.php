<?php

namespace App\Console\Commands;

class RunAnnualBenchmark extends RunBenchmarkScript
{
    protected $signature = 'benchmarks:annual {--year= : Ano a processar}';

    protected $description = 'Executa o benchmark anual';

    public function handle(): int
    {
        $args = $this->option('year') ? ['--year', $this->option('year')] : [];

        $result = $this->runScript('annual_benchmark.py', $args);

        if ($result === self::SUCCESS) {
            $this->info('Benchmark anual executado com sucesso.');
        }

        return $result;
    }
}