<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentType extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentTypeFactory> */
    use HasFactory;

    public function property_contracts(){
        	return $this->hasMany('\App\PropertyContract');
	}
}
