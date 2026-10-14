<?php

namespace App\Models\ServiceProvider;

use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AssistanceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\AssistanceRequestFactory> */
    use HasFactory, SoftDeletes, HasUlids;

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

    protected $fillable = [
        'property_id',
        'user_id',
        'problem_category_id',
        'title',
        'description',
        'status',
        'closed_at',
    ];

    protected $attributes = [
        'status'=>'open',
    ];

    protected $casts = [
        'closed_at'=>'datetime',
    ];

    /**
     * Indica se o pedido tem status 'open'.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
         return $this->status === 'open';
    }

}
