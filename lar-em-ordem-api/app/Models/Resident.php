<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentFactory> */
    use HasFactory, SoftDeletes;

    public function user(){
        	return $this->belongsTo('\App\User');
	}

    public function property_contracts(){
        	return $this->hasMany('\App\PropertyContract');
	}
}
