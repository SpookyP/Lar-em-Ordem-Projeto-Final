<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;
class SocialAccount extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
