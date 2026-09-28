<?php

namespace App\Http\Resources\Vault;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transforma o Model (Eloquent) da Notificação num array estruturado
     * que será convertido em JSON para ser enviado para o frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'document_id' => $this->document_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'alert_date' => $this->alert_date->toIso8601String(),
            'read_at' => $this->read_at ? $this->read_at->toIso8601String() : null,
            'is_read' => $this->read_at !== null,
        ];
    }
}
