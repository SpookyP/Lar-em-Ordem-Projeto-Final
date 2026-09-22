<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyType extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyTypeFactory> */
    use HasFactory, SoftDeletes;

    public function property(){
        	return $this->hasMany(Property::class);
	}
}
