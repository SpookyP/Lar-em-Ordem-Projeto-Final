<?php

namespace App\Models\User;

use App\Models\ServiceProvider\Proposal;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProvider extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProviderFactory> */
    use HasFactory, SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zones()
    {
        return $this->belongsToMany(ServiceZone::class);
    }

   /*  ENTIDADE, PIVOT ENTRE SPEC, CAT E PROVIDER
    public function specialties()
    {
        return $this->hasMany(\App\Models\Provider\ProviderSpecialty::class);
    }
    */

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    protected $attributes = [
        'active' => true,
    ];

    protected $fillable = [
        'user_id',
        'company_name',
        'nif',
        'phone',
        'email',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

}
