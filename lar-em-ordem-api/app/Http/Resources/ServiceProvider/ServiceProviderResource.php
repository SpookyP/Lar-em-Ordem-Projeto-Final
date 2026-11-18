<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // O NIF só é mostrado ao próprio prestador
        $canSeeNif = $user?->id === $this->user_id;

        return [
            'id'             => $this->id,
            'company_name'   => $this->company_name,
            'nif'            => $this->when($canSeeNif, $this->nif),
            'phone'          => $this->phone,
            'provider_email' => $this->provider_email,
            'description'    => $this->description,
            'specialties'    => ProviderSpecialtyResource::collection($this->whenLoaded('specialties')),
            'zones'          => ServiceZoneResource::collection($this->whenLoaded('zones')),
        ];
    }
}
