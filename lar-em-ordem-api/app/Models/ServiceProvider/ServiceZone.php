<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceZone extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceZoneFactory> */
     use HasFactory, SoftDeletes;

    public function providers()
    {
        return $this->belongsToMany(ServiceProvider::class);
    }
}
