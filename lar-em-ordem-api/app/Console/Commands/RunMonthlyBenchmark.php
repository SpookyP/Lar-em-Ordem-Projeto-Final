<?php

namespace App\Console\Commands;

class RunMonthlyBenchmark extends RunBenchmarkScript
{
    protected $signature = 'benchmarks:monthly {--month= : YYYY-MM} {--months-back=3}';

    protected $description = 'Executa o benchmark mensal';

    public function handle(): int
    {
        $args = $this->option('month')
            ? ['--month', $this->option('month')]
            : ['--months-back', (string) $this->option('months-back')];

        $result = $this->runScript('monthly_benchmark.py', $args);

        if ($result === self::SUCCESS) {
            $this->info('Benchmark mensal executado com sucesso.');
        }

        return $result;
    }
}