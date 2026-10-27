<?php

namespace App\Services\Vault;

use App\Models\Vault\Document;
use App\Models\Vault\Notification;
use Carbon\Carbon;

class DocumentExpiryService
{
    /**
     * Verifica todos os documentos com data de validade definida (usado pelo Job diário).
     */
    public function checkAllDocuments(): int
    {
        $count = 0;

        Document::whereNotNull('expiration_date')
            ->chunkById(100, function ($documents) use (&$count) {
                foreach ($documents as $document) {
                    if ($this->checkSingleDocument($document)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Avalia a validade de um único documento e cria notificação se estiver nos escalões de alerta.
     */
    public function checkSingleDocument(Document $document): ?Notification
    {
        if (!$document->expiration_date) {
            return null;
        }

        $today = Carbon::today();
        $expiryDate = Carbon::parse($document->expiration_date)->startOfDay();
        $daysLeft = $today->diffInDays($expiryDate, false); // Negativo se já expirou

        // Definir o escalão de alerta
        if ($daysLeft < 0) {
            $type = 'expired';
            $title = "Documento Expirado: {$document->name}";
            $message = "O documento '{$document->name}' expirou em {$expiryDate->format('d/m/Y')}.";
        } elseif ($daysLeft <= 7) {
            $type = 'expiring_7_days';
            $title = "Urgente: {$document->name} expira em {$daysLeft} dias";
            $message = "O documento '{$document->name}' perde a validade a {$expiryDate->format('d/m/Y')}.";
        } elseif ($daysLeft <= 30) {
            $type = 'expiring_30_days';
            $title = "Aviso: {$document->name} expira em {$daysLeft} dias";
            $message = "Renove o documento '{$document->name}' antes de {$expiryDate->format('d/m/Y')}.";
        } else {
            return null; // Mais de 30 dias de validade, não gera alerta
        }

        // firstOrCreate evita criar notificações repetidas para o mesmo documento e escalão
        return Notification::firstOrCreate(
            [
                'property_id' => $document->property_id,
                'document_id' => $document->id,
                'type'        => $type,
            ],
            [
                'title'      => $title,
                'message'    => $message,
                'alert_date' => now(),
                'read_at'    => null,
            ]
        );
    }
}
