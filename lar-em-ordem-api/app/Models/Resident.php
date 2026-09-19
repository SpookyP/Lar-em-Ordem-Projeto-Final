<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentFactory> */
    use HasFactory;

    public function user(){
        	return $this->belongsTo('\App\User');
	}

    public function property_contracts(){
        	return $this->hasMany('\App\PropertyContract');
	}
}
