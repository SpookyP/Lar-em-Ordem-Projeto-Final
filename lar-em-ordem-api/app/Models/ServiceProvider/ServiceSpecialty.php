<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSpecialty extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceSpecialtyFactory> */
     use HasFactory, SoftDeletes;

    public function problemCategory()
    {
        return $this->belongsTo(ProblemCategory::class);
    }

    public function providers()
    {
        return $this->belongsToMany(ServiceProvider::class);
    }
}
