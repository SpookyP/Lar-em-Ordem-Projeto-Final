<?php
namespace App\Http\Resources\Contract;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,

            'residentType' => $this->whenLoaded('resident_type', fn () => [
                'id'   => $this->resident_type->id,
                'type' => $this->resident_type->type,
            ]),

            'resident' => $this->whenLoaded('resident', fn () => [
                'id'   => $this->resident->id,
                'name' => $this->resident->name,
                'nif'  => $this->resident->nif
            ]),

            'property' => $this->whenLoaded('property', fn () => [
                'id'       => $this->property->id,
                'area'     => $this->property->area,
                'type'     => $this->property->property_type?->type,
                'typology' => $this->property->property_typology?->typology,
            ]),
            'isActive' => $this->is_active,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}