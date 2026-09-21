<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyContract extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyContractFactory> */
    use HasFactory, SoftDeletes;

    public function property(){
        	return $this->belongsTo('\App\Property');
	}
    public function resident(){
        	return $this->belongsTo('\App\Resindent');
	}
    public function resident_type(){
        	return $this->belongsTo('\App\ResindentType');
	}
}
