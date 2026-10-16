<?php
namespace App\Http\Resources\Resident;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nif'         => $this->nif,
            'isActive'  => $this->is_active,
            'user'      => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),

            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}