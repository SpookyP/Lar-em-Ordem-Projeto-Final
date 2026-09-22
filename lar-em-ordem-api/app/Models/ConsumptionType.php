<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConsumptionType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'unit_of_measure',
    ];

    public function consumptions(): HasMany
    {
        return $this->hasMany(Consumption::class);
    }

    public function benchmarks(): HasMany
    {
        return $this->hasMany(ConsumptionBenchmark::class);
    }
}
