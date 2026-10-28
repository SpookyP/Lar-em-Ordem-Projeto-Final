<?php

namespace App\Models\Condominium;

use Illuminate\Database\Eloquent\Model;

class CondominiumDocumentCategory extends Model
{
    protected $fillable = ['name', 'description'];

    public function documents()
    {
        return $this->hasMany(CondominiumDocument::class);
    }
}