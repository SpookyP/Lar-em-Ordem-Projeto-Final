<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\AssistanceRequestFactory> */
    use HasFactory, SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function problemCategory()
    {
        return $this->belongsTo(ProblemCategory::class);
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }
}
