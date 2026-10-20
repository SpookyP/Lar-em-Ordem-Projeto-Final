<?php

namespace App\Http\Resources\ServiceProvider;

use App\Services\FileStorage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->file->original_name,
            'mime_type'     => $this->file->mime_type,
            'size_in_bytes' => $this->file->size_in_bytes,
            'url'           => app(FileStorageService::class)->getSecureUrl($this->file),
            'created_at'    => $this->created_at,
        ];
    }
}