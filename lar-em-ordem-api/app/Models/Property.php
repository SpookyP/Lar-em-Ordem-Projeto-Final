<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    public function property_type(){
        	return $this->belongsTo(PropertyType::class);
	}

    public function property_typology(){
        	return $this->belongsTo(PropertyTypology::class);
	}

    public function address(){
        	return $this->belongsTo(Address::class);
	}

    public function condominium(){
        	return $this->belongsTo(Condominium::class);
	}

	public function residents(){
		return $this->belongsToMany(
			Resident::class,
			'property_contracts',
			'property_id',
			'resident_id'
		)
		->using(PropertyContract::class)
		->withPivot(['start_date', 'end_date', 'resident_type_id', 'is_active'])
		->withTimestamps();
	}
    public function contracts(){
        	return $this->hasMany(PropertyContract::class);
	}
}
