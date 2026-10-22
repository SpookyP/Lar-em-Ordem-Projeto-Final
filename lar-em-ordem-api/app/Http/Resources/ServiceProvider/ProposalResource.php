<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'estimated_value'    => $this->estimated_value,
            'description'        => $this->description,
            'validity'           => $this->validity?->toDateString(),
            'available_date'     => $this->available_date?->toDateString(),
            'available_from'     => $this->available_from ? substr($this->available_from, 0, 5) : null,
            'available_until'    => $this->available_until ? substr($this->available_until, 0, 5) : null,
            'status'             => $this->status,
            'response_date'      => $this->response_date,
            'created_at'         => $this->created_at,
            'service_provider'   => new ServiceProviderResource($this->whenLoaded('serviceProvider')),
            'assistance_request' => new AssistanceRequestResource($this->whenLoaded('assistanceRequest')),
        ];
    }
}