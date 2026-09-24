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
            'type'               => $this->type,
            'url'                => $this->url,
            'start_date'         => $this->start_date->toDateString(),
            'end_date'           => $this->end_date?->toDateString(),
            'active'             => $this->active,
            //'partner'            => new PartnerResource($this->whenLoaded('partner')),
        ];
    }
}