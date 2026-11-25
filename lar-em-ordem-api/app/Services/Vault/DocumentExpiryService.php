<?php

namespace App\Services\Vault;

use App\Events\Vault\VaultNotificationBroadcast;
use App\Models\Vault\Document;
use App\Models\Vault\Notification;
use Carbon\Carbon;

class DocumentExpiryService
{
    /**
     * Checks every document with an expiry date (used by the daily job).
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
     * Checks one document's expiry and creates a notification when it is inside an alert window.
     */
    public function checkSingleDocument(Document $document): ?Notification
    {
        // No expiry date: clear any old alerts for this document_id
        if (!$document->expiration_date) {
            Notification::where('document_id', $document->id)->delete();
            return null;
        }

        $today = Carbon::today();
        $expiryDate = Carbon::parse($document->expiration_date)->startOfDay();
        $daysLeft = $today->diffInDays($expiryDate, false); // Negative if it already expired

        // Pick the alert window
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
            // More than 30 days left: delete this document's old alerts (e.g. it was renewed)
            Notification::where('document_id', $document->id)->delete();
            return null;
        }

        // Clear old notifications of THIS document that belong to ANOTHER window
        Notification::where('document_id', $document->id)
            ->where('type', '!=', $type)
            ->delete();

        // firstOrCreate avoids duplicate notifications for the same document and window
        $notification = Notification::firstOrCreate(
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

        // Only broadcast over the WebSocket when the notification was just created
        if ($notification->wasRecentlyCreated) {
            VaultNotificationBroadcast::dispatch($notification);
        }

        return $notification;
    }
}
