<?php

namespace App\Http\Resources\Condominium;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->user?->name,
            'contact'          => $this->contact,
            'admin_type'       => $this->admin_type,
            'condominia_count' => $this->whenCounted('condominia'),
        ];
    }
}