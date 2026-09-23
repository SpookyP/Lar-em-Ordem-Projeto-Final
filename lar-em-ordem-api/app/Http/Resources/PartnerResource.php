<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'nif'         => $this->nif,
            'phone'       => $this->phone,
            'website'     => $this->website,
            'description' => $this->description,
            'active'      => $this->active,
            //'offers'      => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}
