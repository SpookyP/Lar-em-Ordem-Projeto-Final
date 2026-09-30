<?php

namespace App\Models\Invoice;

use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ConsumptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consumption extends Model
{
    use HasFactory;

        protected static function newFactory()
    {
        return ConsumptionFactory::new();
    }

    protected $fillable = [
        'property_id',
        'invoice_id',
        'consumption_type_id',
        'period_start',
        'period_end',
        'amount',
        'cost',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function consumptionType(): BelongsTo
    {
        return $this->belongsTo(ConsumptionType::class);
    }
}
