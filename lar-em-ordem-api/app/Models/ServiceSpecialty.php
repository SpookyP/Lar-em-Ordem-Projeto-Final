<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
