<?php

namespace App\Models\User;

use App\Models\Property\PropertyContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    /** @use HasFactory<\Database\Factories\ResidentFactory> */
    use HasFactory, SoftDeletes;

    public function user(){
        	return $this->belongsTo(User::class);
	}

    public function properties(){
		return $this->belongsToMany(
			'\App\Property',
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
