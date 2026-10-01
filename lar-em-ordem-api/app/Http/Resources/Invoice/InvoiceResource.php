<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Property\PropertyResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              =>    $this->id,
            'property_id'     =>    $this->property_id,
            'invoice_number'  =>    $this->invoice_number,
            'issue_date'      =>    $this->issue_date?->toDateString(),
            'period_start'    =>    $this->period_start?->toDateString(),
            'period_end'      =>    $this->period_end?->toDateString(),
            'total_amount'    =>    $this->total_amount,
            'supplier'        =>    $this->supplier,

            'property'        =>    $this->whenLoaded( 'property', fn () => PropertyResource::make($this->property) ),

            'consumptions'    =>    ConsumptionResource::collection( $this->whenLoaded('consumptions') ),

            'created_at'      =>    $this->created_at,
            'updated_at'      =>    $this->updated_at,
        ];
    }
}
