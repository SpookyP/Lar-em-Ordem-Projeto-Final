<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceSpecialtyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'problem_category_id' => $this->problem_category_id,
            'name'                => $this->name,
            'description'         => $this->description,
        ];
    }
}