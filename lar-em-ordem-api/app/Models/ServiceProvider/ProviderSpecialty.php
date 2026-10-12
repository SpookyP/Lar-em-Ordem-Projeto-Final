<?php

namespace App\Models\ServiceProvider;

use App\Models\User\ServiceProvider as ServiceProviderModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;


class ProviderSpecialty extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'problem_category_id',
        'service_specialty_id',
        'custom_name',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProviderModel::class);
    }

    public function category()
    {
        return $this->belongsTo(ProblemCategory::class, 'problem_category_id');
    }

    public function specialty()
    {
        return $this->belongsTo(ServiceSpecialty::class, 'service_specialty_id');
    }

    
    protected function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->custom_name ?? $this->specialty?->name);
    }
}