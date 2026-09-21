<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condominium extends Model
{
    /** @use HasFactory<\Database\Factories\CondominiumFactory> */
    use HasFactory, SoftDeletes;

    public function property(){
        	return $this->hasMany('\App\Property');
	}
    public function Address(){
        	return $this->belongsTO('\App\Address');
	}
}
