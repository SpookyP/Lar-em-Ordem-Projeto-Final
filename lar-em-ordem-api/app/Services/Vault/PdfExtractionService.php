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
    public function extractInfo(string $path): array
    {
        try {
            $process = new Process([
                config('services.python.binary', 'python3'),
                base_path('scripts/extract_pdf.py'),
                $path,
            ]);
            $process->setTimeout(30);
            $process->mustRun();

            return json_decode($process->getOutput(), true) ?? [];
        } catch (\Throwable $e) {
            \Log::warning('Falha na extração do PDF', ['erro' => $e->getMessage()]);
            return [];
        }
    }
}
