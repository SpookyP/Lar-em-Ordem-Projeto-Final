<?php

namespace App\Models\Property;

use App\Models\User\Resident;
use App\Models\User\ResidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Validation\ValidationException;

class PropertyContract extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyContractFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    protected $fillable = [
        'property_id',
        'resident_id',
        'start_date',
        'end_date',
        'resident_type_id',
        'is_active',
    ];

    protected static function booted(): void
    {
        // Only one active contract per property is allowed.
        static::saving(function (PropertyContract $contract) {
            if (! $contract->is_active) {
                return;
            }

            $taken = static::where('property_id', $contract->property_id)
                ->where('is_active', true)
                ->when($contract->exists, fn ($query) => $query->whereKeyNot($contract->getKey()))
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'property_id' => ['Esta casa já tem um contrato ativo.'],
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_active'  => 'boolean',
        ];
    }

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
