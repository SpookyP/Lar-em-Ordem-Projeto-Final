<?php

namespace App\Http\Resources\PartnerOffer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'description'        => $this->description,
            'category'           => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'url'                => $this->url,
            'start_date'         => $this->start_date->toDateString(),
            'end_date'           => $this->end_date?->toDateString(),
            'active'             => $this->active,
            // Como a relação devolve um único objeto/modelo, instanciamos um único Resource.
            'partner'            => new PartnerResource($this->whenLoaded('partner')),
        ];
    }
}