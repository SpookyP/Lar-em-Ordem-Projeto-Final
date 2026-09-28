<?php

namespace App\Http\Resources\Vault;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transforma o Model Document num array estruturado para o frontend.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'document_category_id' => $this->document_category_id,
            'name' => $this->name,
            'description' => $this->description,
            'file_path' => $this->file_path,

            // Formatação segura de datas
            'issue_date' => $this->data_emissao ? $this->data_emissao->toIso8601String() : null,
            'expiration_date' => $this->expiration_date ? $this->expiration_date->toIso8601String() : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
