<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    public function properties(){
        	return $this->hasMany('\App\Property');
	}

    public function condominiums(){
        	return $this->hasMany('\App\Condominium');
	}
}
