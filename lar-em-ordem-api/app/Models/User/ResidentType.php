<?php

namespace App\Models\User;

use App\Models\Property\PropertyContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResidentType extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentTypeFactory> */
    use HasFactory, SoftDeletes;
    
    public function contracts(){
        	return $this->hasMany(PropertyContract::class,'resident_type_id');
	}
}
