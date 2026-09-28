<?php
namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_name' => $this->company_name,
            'nif'          => $this->nif,
            'phone'        => $this->phone,
            'email'        => $this->email,
            'description'  => $this->description,
            'active'       => $this->active,
            // 'specialties' => ProviderSpecialtyResource::collection($this->whenLoaded('specialties')),
            // 'zones'       => ServiceZoneResource::collection($this->whenLoaded('zones')),
        ];
    }
}