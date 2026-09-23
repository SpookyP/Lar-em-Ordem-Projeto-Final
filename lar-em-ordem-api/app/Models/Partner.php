<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    /** @use HasFactory<\Database\Factories\PartnerFactory> */
     use HasFactory, SoftDeletes;

     public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    protected $attributes = [
        'active' => true,
    ];

    //forca a conversao 0/1 da bdd para false/true
    protected $casts = [
        'active' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'nif',
        'phone',
        'website',
        'description',
        'user_id',
        'active',
    ];

}
