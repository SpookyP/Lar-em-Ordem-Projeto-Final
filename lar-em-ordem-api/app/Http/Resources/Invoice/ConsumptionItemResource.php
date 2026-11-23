<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       =>   $this->id,
            'name'     =>   $this->name,
            'category' =>   $this->category,
            'amount'   =>   $this->amount,
            'unit'     =>   $this->unit,
            'cost'     =>   $this->cost,
        ];
    }
}