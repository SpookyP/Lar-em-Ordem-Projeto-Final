<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'area'        => $this->area,
            'fraction'    => $this->fraction,

            'type' => $this->whenLoaded('property_type', fn () => [
                'id'   => $this->property_type->id,
                'type' => $this->property_type->type,
            ]),

            'typology' => $this->whenLoaded('property_typology', fn () => [
                'id'   => $this->property_typology->id,
                'typology' => $this->property_typology->typology,
            ]),

            'address' => $this->whenLoaded('address', fn () => [
                'id'         => $this->address->id,
                'street'     => $this->address->street,
                'postal_code' => $this->address->postal_code,
                'door'       => $this->address->door,
                'county'     => $this->address->county,
                'location'   => $this->address->location,
                'district'   => $this->address->district,
            ]),

            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}