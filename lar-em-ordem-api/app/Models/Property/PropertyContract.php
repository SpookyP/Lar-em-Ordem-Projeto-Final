<?php

namespace App\Models\Property;

use App\Models\User\Resident;
use App\Models\User\ResidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyContract extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyContractFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'resident_id',
        'start_date',
        'end_date',
        'resident_type_id',
        'is_active',
    ];

    public function property(){
        	return $this->belongsTo(Property::class,'property_id');
	}
    public function resident(){
        	return $this->belongsTo(Resident::class,'resident_id');
	}
	public function residentType()
    {
        return $this->belongsTo(ResidentType::class, 'resident_type_id');
    }
}
