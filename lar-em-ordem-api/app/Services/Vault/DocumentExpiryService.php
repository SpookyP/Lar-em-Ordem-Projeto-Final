<?php

namespace App\Services\Vault;

use App\Models\Vault\Document;
use App\Models\Vault\Notification;
use Illuminate\Support\Carbon;

class DocumentExpiryService
{
    /**
     * Verifica documentos a expirar num intervalo de dias e gera notificações.
     * Retorna o número total de novos alertas criados para efeitos de log.
     */
    public function generateExpiryAlerts(int $daysWarning = 30): int
    {
        // Define a janela temporal de pesquisa.
        // startOfDay() garante que a pesquisa começa às 00:00:00.
        $targetDate = now()->addDays($daysWarning)->startOfDay();
        $today = now()->startOfDay();

        $notificationsCreated = 0;

        // Procurar documentos cuja validade cai entre hoje e daqui a 30 dias
        $expiringDocuments = Document::whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$today, $targetDate])
            ->get();

        foreach ($expiringDocuments as $document) {
            // Verificar se já gerámos um alerta de caducidade para este documento específico
            $alertExists = Notification::where('document_id', $document->id)
                ->where('type', 'document_expiry')
                ->exists();

            if (!$alertExists) {
                Notification::create([
                    'property_id' => $document->property_id,
                    'document_id' => $document->id,
                    'type' => 'document_expiry',
                    'title' => 'Documento a Expirar',
                    'message' => "O documento '{$document->name}' expira no dia " . Carbon::parse($document->expiration_date)->format('d/m/Y') . ".",
                    'alert_date' => now(),
                ]);

                $notificationsCreated++;
            }
        }

        return $notificationsCreated;
    }
}
