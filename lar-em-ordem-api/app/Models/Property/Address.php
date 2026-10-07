<?php

namespace App\Models\Property;

use App\Models\Condominium\Condominium;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    protected $fillable = ['street',
    'postal_code',
    'door',
    'county',
    'location',
    'district'];

    public function properties(){
        	return $this->hasMany(Property::class);
	}

    public function condominiums(){
        	return $this->hasMany(Condominium::class);
	}
}
