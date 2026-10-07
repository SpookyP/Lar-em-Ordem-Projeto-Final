<?php

namespace App\Models\Condominium;

use App\Models\Property\Property;
use App\Models\Property\Address;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Condominium extends Model
{
    /** @use HasFactory<\Database\Factories\CondominiumFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    public function property(){
        	return $this->hasMany(Property::class);
	}
    public function Address(){
        	return $this->belongsTo(Address::class);
	}
}
