<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyTypeFactory> */
    use HasFactory;

    public function property(){
        	return $this->hasMany('\App\Property');
	}
}
