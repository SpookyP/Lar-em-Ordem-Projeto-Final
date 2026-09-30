<?php
namespace App\Http\Resources\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'addressId'        => $this->address_id,
            'area'             => $this->area,
            'fraction'         => $this->fraction,

            // Inline Address transformation
            'address'          => $this->whenLoaded('address', fn() => [
                'id'         => $this->address->id,
                'street'     => $this->address->street,
                'postalCode' => $this->address->postal_code,
                'door'       => $this->address->door,
                'county'     => $this->address->county,
                'location'   => $this->address->location,
                'district'   => $this->address->district,
            ]),

            'propertyType'     => $this->whenLoaded('propertyType'),
            'propertyTypology' => $this->whenLoaded('propertyTypology'),
            'condominium'      => $this->whenLoaded('condominium'),

            // Inline Contracts transformation
            'contracts'        => $this->whenLoaded('contracts', fn() => $this->contracts->map(fn($contract) => [
                'id'           => $contract->id,
                'residentId'   => $contract->resident_id,
                'residentType' => $contract->relationLoaded('residentType') ? $contract->residentType : null,
                'startDate'    => $contract->start_date,
                'endDate'      => $contract->end_date,
                'isActive'     => (bool) $contract->is_active,
            ])),

            'createdAt'        => $this->created_at,
            'updatedAt'        => $this->updated_at,
        ];
    }
}