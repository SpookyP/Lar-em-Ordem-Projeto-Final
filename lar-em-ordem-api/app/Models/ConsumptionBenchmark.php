<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionBenchmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'consumption_type_id',
        'property_type_id',
        'typology_id',
        'area',
        'reference_period',
        'average_value',
    ];

    public function propertytype(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function propertytypology(): BelongsTo
    {
        return $this->belongsTo(PropertyTypology::class);
    }

    public function consumptiontype(): BelongsTo
    {
        return $this->belongsTo(ConsumptionType::class);
    }
}