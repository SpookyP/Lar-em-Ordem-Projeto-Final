<?php

namespace App\Services\Vault;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PdfExtractionService
{
    /**
     * Extrai dados de um PDF usando o caminho local do ficheiro.
     */
    public function extractInfo(string $absolutePath): array
    {
        $pythonBinary = config('invoices.python_bin');

        if (!$pythonBinary || !file_exists($pythonBinary)) {
            $pythonBinary = 'python';
        }

        if (!file_exists($absolutePath)) {
            Log::error('Ficheiro PDF não encontrado no caminho local: ' . $absolutePath);
            return [];
        }

        $process = new Process([
            $pythonBinary,
            base_path('scripts/extract_pdf.py'),
            $absolutePath,
        ]);

        $process->setTimeout(30);

        try {
            $process->run();
        } catch (\Throwable $e) {
            Log::warning('Extração de PDF indisponível: ' . $e->getMessage());
            return [];
        }

        if (!$process->isSuccessful()) {
            Log::warning('A extração de PDF falhou: ' . $process->getErrorOutput());
            return [];
        }

        $output = json_decode(trim($process->getOutput()), true);

        if (!is_array($output) || ($output['status'] ?? null) !== 'success') {
            return [];
        }

        return $output;
    }
}
