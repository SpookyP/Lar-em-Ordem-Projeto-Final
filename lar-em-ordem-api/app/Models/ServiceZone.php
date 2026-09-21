<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceZone extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceZoneFactory> */
     use HasFactory, SoftDeletes;

    public function providers()
    {
        return $this->belongsToMany(ServiceProvider::class);
    }
}
