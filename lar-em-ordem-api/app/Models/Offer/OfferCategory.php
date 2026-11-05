<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;

class OfferCategory extends Model
{
    protected $fillable = ['name'];

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}