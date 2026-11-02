<?php

namespace App\Http\Resources\Condominium;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumNoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'message'       => $this->message,
            'priority'      => $this->priority,
            'published_at'  => $this->published_at,
            'expires_at'    => $this->expires_at,
            'read'          => $this->whenHas('read', fn () => (bool) $this->read),
            'readers_count' => $this->whenCounted('readers'),
        ];
    }
}