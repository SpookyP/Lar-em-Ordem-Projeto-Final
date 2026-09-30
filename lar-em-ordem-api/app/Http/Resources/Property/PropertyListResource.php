<?php

namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'addressId'    => $this->address_id,
            'residentType' => $this->whenLoaded('contracts', function () {
                return $this->contracts->first()?->residentType?->type;
            }),
            'fraction'     => $this->fraction,
            'street'       => $this->whenLoaded('address', fn() => $this->address->street),
            'location'     => $this->whenLoaded('address', fn() => $this->address->location),
            'propertyType' => $this->whenLoaded('propertyType', fn() => $this->propertyType->type),
            'typology'     => $this->whenLoaded('propertyTypology', fn() => $this->propertyTypology->typology),
        ];
    }
}
