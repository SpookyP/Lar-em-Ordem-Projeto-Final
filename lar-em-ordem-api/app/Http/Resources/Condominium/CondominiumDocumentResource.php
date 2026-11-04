<?php

namespace App\Http\Resources\Condominium;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CondominiumDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'document_date' => $this->document_date?->toDateString(),
            'category'      => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            // dados do ficheiro para mostrar na lista (o link é pedido em /download)
            'file'          => $this->whenLoaded('file', fn () => [
                'name'      => $this->file->original_name,
                'mime_type' => $this->file->mime_type,
                'size'      => $this->file->size_in_bytes,
            ]),
            'created_at'    => $this->created_at,
        ];
    }
}