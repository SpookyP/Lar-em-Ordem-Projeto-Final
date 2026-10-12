<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderSpecialtyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'display_name' => $this->display_name,
            'is_custom'    => $this->custom_name !== null,
            'category'     => new ProblemCategoryResource($this->whenLoaded('category')),
            'specialty'    => new ServiceSpecialtyResource($this->whenLoaded('specialty')),
        ];
    }
}