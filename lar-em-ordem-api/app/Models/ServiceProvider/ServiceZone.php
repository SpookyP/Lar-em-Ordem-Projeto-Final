<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User\ServiceProvider as ServiceProviderModel;

class ServiceZone extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceZoneFactory> */
     use HasFactory, SoftDeletes;

    public function providers()
    {
        return $this->belongsToMany(ServiceProviderModel::class)->withTimestamps();
    }

    protected $fillable = [
        'district',
        'county',
        'location',
    ];
}
