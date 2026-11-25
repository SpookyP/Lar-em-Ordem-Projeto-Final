<?php

namespace App\Services\Invoice;

use App\Models\Invoice\ConsumptionType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InvoiceExtractionService
{
    /**
     * Runs the Python script on the PDF and returns the extracted data.
     * Returns null when the extraction fails (the user then fills the form by hand).
     *
     * @return array<string, mixed>|null
     */
    public function extract(string $pdfPath): ?array
    {
        $types = ConsumptionType::query()
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);

        $process = new Process([
            config('invoices.python_bin'),
            base_path(config('invoices.extract_script')),
            $pdfPath,
        ]);

        $process->setTimeout(config('invoices.extract_timeout'));
        $process->setInput($types->toJson(JSON_UNESCAPED_UNICODE)); // through stdin, avoids quoting problems on Windows

        try {
            $process->run();
        } catch (\Throwable $e) {
            report($e); // timeout or Python not found

            return null;
        }

        if (! $process->isSuccessful()) {
            Log::warning('Falha na extração dos dados da fatura', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => Str::limit($process->getErrorOutput(), 500),
            ]);

            return null;
        }

        $data = json_decode($process->getOutput(), true);

        return is_array($data) ? $data : null;
    }
}