<?php

namespace App\Models\Property;

use App\Models\Condominium;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory, SoftDeletes;

    public function properties(){
        	return $this->hasMany(Property::class);
	}

    public function condominiums(){
        	return $this->hasMany(Condominium::class);
	}
}
