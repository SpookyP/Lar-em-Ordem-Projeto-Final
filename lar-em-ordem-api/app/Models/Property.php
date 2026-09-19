<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory;

    public function property_type(){
        	return $this->belongsTo('\App\PropertyType');
	}

    public function property_typology(){
        	return $this->belongsTo('\App\PropertyTypology');
	}

    public function address(){
        	return $this->belongsTo('\App\Address');
	}

    public function condominium(){
        	return $this->belongsTo('\App\Condominium');
	}

    public function property_contracts(){
        	return $this->hasMany('\App\PropertyContract');
	}
}
