<?php

namespace App\Http\Resources\PartnerOffer;

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
        $user = $request->user();

        // O NIF só é mostrado ao próprio parceiro
        $canSeeNif = $user && ($user->id === $this->user_id || $user->hasRole('SU'));

        return [
            'id'          => $this->id,
            'company_name' => $this->company_name,
            'nif'         => $this->when($canSeeNif, $this->nif),
            'phone'       => $this->phone,
            'website'     => $this->website,
            'description' => $this->description,
            'offers'      => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}
