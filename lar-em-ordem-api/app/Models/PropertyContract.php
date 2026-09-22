<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyContract extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyContractFactory> */
    use HasFactory, SoftDeletes;

    public function property(){
        	return $this->belongsTo(Property::class,'property_id');
	}
    public function resident(){
        	return $this->belongsTo(Resident::class,'resident_id');
	}
	public function residentType()
    {
        return $this->belongsTo(ResidentType::class, 'resident_type_id');
    }
}
