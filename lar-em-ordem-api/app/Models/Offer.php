<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory, SoftDeletes;

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    protected $attributes = [
        'active' => true,
    ];

    protected $fillable = [
        'partner_id',
        'title',
        'description',
        'type',
        'url',
        'start_date',
        'end_date',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
}
