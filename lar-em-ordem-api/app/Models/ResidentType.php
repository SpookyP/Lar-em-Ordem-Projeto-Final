<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResidentType extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentTypeFactory> */
    use HasFactory, SoftDeletes;

    public function property_contracts(){
        	return $this->hasMany('\App\PropertyContract');
	}
}
