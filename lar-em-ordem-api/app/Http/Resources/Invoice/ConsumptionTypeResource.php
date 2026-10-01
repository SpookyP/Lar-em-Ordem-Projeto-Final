<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                =>  $this->id,
            'name'              =>  $this->name,
            'unit_of_measure'   =>  $this->unit_of_measure,
        ];
    }
}
