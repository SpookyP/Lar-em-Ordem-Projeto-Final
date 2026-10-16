<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssistanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->user_id;

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
            'closed_at'   => $this->closed_at,
            'category'    => new ProblemCategoryResource($this->whenLoaded('problemCategory')),
            'property'    => $this->whenLoaded('property', fn () => [
                'id'       => $this->property->id,
                'fraction' => $isOwner ? $this->property->fraction : null,
                'street'   => $isOwner ? $this->property->address?->street : null,
                'location' => $this->property->address?->location,
                'county'   => $this->property->address?->county,
                'district' => $this->property->address?->district,
            ]),
        ];
    }
}