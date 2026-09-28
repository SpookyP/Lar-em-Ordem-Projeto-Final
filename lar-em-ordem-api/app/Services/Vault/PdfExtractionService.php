<?php

namespace App\Services\Vault;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

class PdfExtractionService
{
    /**
     * Executa um script Python em segundo plano para extrair informações textuais
     * e metadados de um ficheiro PDF específico.
     *
     * @param string $absolutePath O caminho absoluto no servidor para o ficheiro PDF a ser lido.
     * @return array Um array associativo contendo os dados extraídos (ex: status, text, dates).
     * @throws ProcessFailedException Se a execução do comando no terminal falhar a nível de sistema.
     * @throws RuntimeException Se o script Python for executado mas reportar um erro na extração.
     */
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
