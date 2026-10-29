<?php

namespace App\Http\Resources\Vault;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at,
            'alert_date' => $this->alert_date,
            'document_id' => $this->document_id,
            'document' => [
                'id' => $this->whenLoaded('document', fn() => $this->document->id),
                'name' => $this->whenLoaded('document', fn() => $this->document->name),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
