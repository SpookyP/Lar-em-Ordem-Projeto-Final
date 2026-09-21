<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyTypology extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyTypologyFactory> */
    use HasFactory, SoftDeletes;

    public function property(){
        	return $this->hasMany('\App\Property');
	}
}
