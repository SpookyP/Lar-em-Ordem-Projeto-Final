<?php

namespace App\Models\ServiceProvider;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProvider extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProviderFactory> */
    use HasFactory, SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

      public function specialties()
    {
        return $this->belongsToMany(ServiceSpecialty::class);
    }

    public function zones()
    {
        return $this->belongsToMany(ServiceZone::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

}
