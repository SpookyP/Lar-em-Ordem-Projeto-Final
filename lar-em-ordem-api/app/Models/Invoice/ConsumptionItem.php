<?php

namespace App\Models\Invoice;

use Database\Factories\ConsumptionItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ConsumptionItem extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return ConsumptionItemFactory::new();
    }
    
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cost'   => 'decimal:2',
        ];
    }

    protected $fillable = [
        'consumption_id',
        'name',
        'category',
        'amount',
        'unit',
        'cost',
    ];

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(Consumption::class);
    }
}
