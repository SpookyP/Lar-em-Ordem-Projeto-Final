<?php

namespace App\Models\Condominium;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CondominiumAdmin extends Model
{
    /** @use HasFactory<\Database\Factories\Condominium\CondominiumAdminFactory> */
    use HasFactory, SoftDeletes, HasUlids;

    protected $fillable = ['user_id', 'name', 'contact', 'admin_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function condominia()
    {
        return $this->hasMany(Condominium::class);
    }
}