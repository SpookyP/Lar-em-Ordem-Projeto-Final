<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistanceRequestResource extends JsonResource
{
   public function toArray(Request $request): array
    {
        $user = $request->user();

        $canSeeAddress = $this->resource->isOwnedBy($user)
            || ($user && $this->relationLoaded('acceptedProposal')
                && $this->acceptedProposal?->serviceProvider?->user_id === $user->id);

        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'description'       => $this->description,
            'status'            => $this->status,
            'created_at'        => $this->created_at,
            'closed_at'         => $this->closed_at,
            'pending_proposals_count' => $this->whenCounted('pending_proposals'),
            'category'          => new ProblemCategoryResource($this->whenLoaded('problemCategory')),
            'accepted_proposal' => new ProposalResource($this->whenLoaded('acceptedProposal')),
            'attachments'       => RequestAttachmentResource::collection($this->whenLoaded('attachments')),
            'property'          => $this->whenLoaded('property', fn () => [
                'id'       => $canSeeAddress ? $this->property->id : null,
                'fraction' => $canSeeAddress ? $this->property->address?->fraction : null,
                'street'   => $canSeeAddress ? $this->property->address?->street : null,
                'location' => $this->property->address?->location,
                'county'   => $this->property->address?->county,
                'district' => $this->property->address?->district,
            ]),
            'proposals'         => ProposalResource::collection($this->whenLoaded('proposals')),
        ];
    }
}