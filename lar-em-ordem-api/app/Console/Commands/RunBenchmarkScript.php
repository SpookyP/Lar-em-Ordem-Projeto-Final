<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Base for the commands that run one of the Python scripts in scripts/Benchmark_creation.
 */
abstract class RunBenchmarkScript extends Command
{
    /**
     * @param  list<string>  $arguments
     */
    protected function runScript(string $script, array $arguments = []): int
    {
        $process = new Process([
            config('benchmarks.python_bin'),
            base_path(config('benchmarks.scripts_path') . '/' . $script),
            ...$arguments,
        ]);

        // the backfill goes through every month with data, so it can take a while
        $process->setTimeout(3600);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            $this->error("{$script} falhou.");
            logger()->error("{$script} falhou", ['stderr' => $process->getErrorOutput()]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}