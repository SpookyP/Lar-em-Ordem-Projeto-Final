<?php

namespace App\Console\Commands;

use App\Models\File\File;
use App\Services\FileStorage\FileStorageService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Console\Command;
use Throwable;

class CleanOrphanedInvoiceFiles extends Command
{
    protected $signature = 'invoices:clean-orphaned-files {--dry-run : Lista os ficheiros que seriam eliminados, sem os apagar}';

    protected $description = 'Remove ficheiros de faturas sem fatura associada e com mais de 24 horas.';

    public function handle(FileStorageService $files): int
    {
        $cutoff = now()->subDay();

        // a file is uploaded before its invoice is saved: the 24 h margin spares the ones still
        // being registered, and the rest are leftovers from failed requests
        $query = File::query()
            ->where('category', InvoiceService::FILE_CATEGORY)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('invoice');

        if ($this->option('dry-run')) {
            $count = (clone $query)->count();

            $this->info("Modo de simulação: {$count} ficheiro(s) órfão(s) encontrado(s).");

            (clone $query)
                ->orderBy('id')
                ->chunkById(100, function ($batch) {
                    foreach ($batch as $file) {
                        $this->line("- {$file->original_name} [{$file->id}]");
                    }
                });

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        // Processes in batches of 100, so the files aren't all loaded into memory
        $query
            ->orderBy('id')
            ->chunkById(100, function ($batch) use ($files, &$deleted, &$failed) {
                foreach ($batch as $file) {
                    try {
                        if ($files->deleteFile($file)) {
                            $deleted++;
                            $this->line("Ficheiro eliminado: {$file->original_name} [{$file->id}]");
                        } else {
                            $failed++;
                            $this->warn("Não foi possível eliminar o registo: {$file->id}");
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                        $this->error("Falha ao eliminar o ficheiro {$file->id}: {$exception->getMessage()}");
                    }
                }
            });

        $this->info("Limpeza concluída. Eliminados: {$deleted}. Falhas: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
