<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProblemCategory extends Model
{
    /** @use HasFactory<\Database\Factories\ProblemCategoryFactory> */
    use HasFactory, SoftDeletes;

     public function specialties()
    {
        return $this->hasMany(ServiceSpecialty::class);
    }

    public function requests()
    {
        return $this->hasMany(AssistanceRequest::class);
    }
}
