<?php

namespace App\Services\Vault;

use App\Models\File\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use RuntimeException;

class PdfExtractionService
{
    /**
     * Permite extrair dados a partir de um ficheiro que já está no Cloudflare R2.
     * Útil se quiseres reprocessar documentos no futuro via Jobs ou Consola.
     */
    public function extractFromCloudFile(File|string $file): array
    {
        $disk = config('filesystems.default', 's3');
        $path = $file instanceof File ? $file->path : $file;

        // Criar ficheiro temporário local com extensão .pdf para o pdfplumber
        $tempPath = tempnam(sys_get_temp_dir(), 'vault_pdf_') . '.pdf';

        try {
            // Descarregar o stream do Cloudflare R2 para o ficheiro temporário
            $stream = Storage::disk($disk)->readStream($path);
            if (!$stream) {
                throw new RuntimeException("Não foi possível ler o ficheiro do disco: {$path}");
            }

            file_put_contents($tempPath, stream_get_contents($stream));

            if (is_resource($stream)) {
                fclose($stream);
            }

            // Executar o script Python sobre o ficheiro temporário
            return $this->extractInfo($tempPath);
        } finally {
            // Limpar sempre o ficheiro temporário
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Extrai dados de um PDF usando um caminho 100% local
     * (UploadedFile real path ou o Ficheiro temporário criado acima).
     */
    public function extractInfo(string $absolutePath): array
    {
        // Força explicitamente o caminho absoluto do .venv do teu projeto
        $pythonBinary = '/usr/bin/python';

        if (!file_exists($pythonBinary)) {
            throw new \RuntimeException('O executável Python do .venv não foi encontrado no caminho absoluto!');
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

        // Força o Python a procurar nos diretórios globais e locais do utilizador hyper
        $process->setEnv([
            'PYTHONPATH' => '/usr/lib/python3.14/site-packages:/home/hyper/.local/lib/python3.14/site-packages',
            'PATH' => '/usr/bin:/bin',
        ]);

        $process->setTimeout(30);
        $process->run();

        // Se falhar, vamos ver exatamente qual foi o erro devolvido
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('PYTHON PATH USADO: ' . $pythonBinary . ' | ERRO: ' . $process->getErrorOutput());
        }

        $output = json_decode(trim($process->getOutput()), true);

        $isSuccess = (isset($output['status']) && $output['status'] === 'success') ||
            (isset($output['sucesso']) && $output['sucesso'] === true);

        if (!is_array($output) || !$isSuccess) {
            return [];
        }

        return $output;
    }
}
