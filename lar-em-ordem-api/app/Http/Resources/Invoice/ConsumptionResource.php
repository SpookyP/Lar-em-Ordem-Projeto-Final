<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Property\PropertyResource;
use App\Http\Resources\Invoice\ConsumptionTypeResource;

class ConsumptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    =>      $this->id,
            'invoice_id'            =>      $this->invoice_id,
            'property_id'           =>      $this->property_id,
            'consumption_type_id'   =>      $this->consumption_type_id,
            'period_start'          =>      $this->period_start?->toDateString(),
            'period_end'            =>      $this->period_end?->toDateString(),
            'amount'                =>      $this->amount,
            'cost'                  =>      $this->cost,

            'invoice'               =>      $this->whenLoaded( 'invoice', fn () => InvoiceResource::make($this->invoice) ),
            'property'              =>      $this->whenLoaded( 'property', fn () => PropertyResource::make($this->property) ),
            'consumption_type'      =>      $this->whenLoaded( 'consumptionType', fn () => ConsumptionTypeResource::make($this->consumptionType) ),

            'created_at'            =>      $this->created_at,
            'updated_at'            =>      $this->updated_at,
        ];
    }
}
