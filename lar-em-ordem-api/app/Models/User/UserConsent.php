<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One answer to one consent. A new answer is a new row (history), so there is no updated_at.
 */
class UserConsent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'granted',
        'policy_version',
        'source',
    ];

    protected function casts()
    {
        return [
            'granted' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}