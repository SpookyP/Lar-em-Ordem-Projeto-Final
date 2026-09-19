<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyTypology extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyTypologyFactory> */
    use HasFactory;

    public function property(){
        	return $this->hasMany('\App\Property');
	}
}
