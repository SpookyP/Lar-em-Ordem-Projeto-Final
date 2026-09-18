<?php

namespace App\Services\Vault;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

class PdfExtractionService
{
    public function extractInfo(string $absolutePath): array
    {
        $process = new Process(['python3', base_path('scripts/extract_pdf.py'), $absolutePath]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = json_decode($process->getOutput(), true);

        if (isset($output['status']) && $output['status'] === 'error') {
            throw new RuntimeException("PDF Extraction Error: " . $output['message']);
        }

        return $output;
    }
}
