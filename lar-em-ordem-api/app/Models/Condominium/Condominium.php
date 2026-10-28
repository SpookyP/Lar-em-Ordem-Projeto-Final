<?php

namespace App\Models\Condominium;

use App\Models\Property\Address;
use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Condominium extends Model
{
    /** @use HasFactory<\Database\Factories\Condominium\CondominiumFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    protected $table = 'condominia';

    protected $fillable = [
        'condominium_admin_id',
        'address_id',
        'name',
        'reserve_fund',
        'invite_code',
    ];

    protected $hidden = ['transfer_code_hash'];

    protected function casts(): array
    {
        return [
            'reserve_fund'        => 'decimal:2',
            'transfer_expires_at' => 'datetime',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(CondominiumAdmin::class, 'condominium_admin_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function notices()
    {
        return $this->hasMany(CondominiumNotice::class);
    }

    public function documents()
    {
        return $this->hasMany(CondominiumDocument::class);
    }
}