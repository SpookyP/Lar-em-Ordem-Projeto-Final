<?php

namespace App\Models\Invoice;

use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumptionBenchmark extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'consumption_type_id',
        'property_type_id',
        'typology_id',
        'region',
        'period_start',
        'reference_period',
        'average_value',
        'average_cost',
        'sample_size',
    ];

    protected $casts = [
        'average_value' => 'decimal:3',
        'average_cost' => 'decimal:3',
    ];

    public function typology(): BelongsTo
    {
        return $this->belongsTo(
            PropertyTypology::class,
            'typology_id'
        );
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(
            PropertyType::class,
            'property_type_id'
        );
    }

    public function consumptionType(): BelongsTo
    {
        return $this->belongsTo(
            ConsumptionType::class,
            'consumption_type_id'
        );
    }
}
