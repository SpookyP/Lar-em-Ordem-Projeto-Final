<?php

namespace App\Services\Invoice;

use App\Models\Invoice\ConsumptionType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InvoiceExtractionService
{
    /**
     * Corre o script Python sobre o PDF e devolve os dados extraídos.
     * Devolve null se a extração falhar (o utilizador preenche o formulário à mão).
     *
     * @return array<string, mixed>|null
     */
    public function extract(string $pdfPath): ?array
    {
        // Tipos de consumo da BD para o script mapear
        $types = ConsumptionType::query()
            ->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);

        // Argumentos em array: o caminho nunca é interpretado como comando
        $process = new Process([
            config('invoices.python_bin'),
            base_path(config('invoices.extract_script')),
            $pdfPath,
        ]);

        $process->setTimeout(config('invoices.extract_timeout'));
        $process->setInput($types->toJson(JSON_UNESCAPED_UNICODE)); // via stdin, evita problemas de aspas no Windows

        try {
            $process->run();
        } catch (\Throwable $e) {
            report($e); // timeout ou Python não encontrado

            return null;
        }

        if (! $process->isSuccessful()) {
            // Não regista o conteúdo do PDF nem dos dados extraídos
            Log::warning('Invoice extraction failed', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => Str::limit($process->getErrorOutput(), 500),
            ]);

            return null;
        }

        $data = json_decode($process->getOutput(), true);

        return is_array($data) ? $data : null;
    }
}