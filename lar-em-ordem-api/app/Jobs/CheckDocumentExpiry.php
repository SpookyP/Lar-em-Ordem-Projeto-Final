<?php

namespace App\Jobs;

use App\Services\Vault\DocumentExpiryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDocumentExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O método handle é o "motor" do Job. É executado automaticamente pelo Laravel
     * quando o processo é despachado (ex: pelo agendador diário).
     *
     * O Laravel faz Injeção de Dependência (DI) automática, entregando uma
     * instância pronta a usar do teu DocumentExpiryService.
     */
    public function handle(DocumentExpiryService $expiryService): void
    {
        $expiryService->checkAllDocuments();
    }
}
